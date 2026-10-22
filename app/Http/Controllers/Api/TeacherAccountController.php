<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetTeacherPasswordRequest;
use App\Http\Requests\StoreTeacherAccountRequest;
use App\Http\Requests\UpdateTeacherAccountRequest;
use App\Mail\TeacherWelcomeMail;
use App\Models\Teacher;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class TeacherAccountController extends Controller
{
    private const CACHE_VERSION_KEY = 'admin_teacher_accounts:version';

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', Rule::in([10, 20, 50, 100])],
        ]);
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 20);
        $version = Cache::rememberForever(self::CACHE_VERSION_KEY, fn (): string => (string) Str::uuid());
        $cacheKey = "admin_teacher_accounts:{$version}:{$page}:{$perPage}";

        $result = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($page, $perPage): array {
            $teachers = User::query()
                ->where('role', 'teacher')
                ->whereHas('teacher')
                ->with('teacher')
                ->orderBy('name')
                ->orderBy('id')
                ->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => $teachers->getCollection()->map(fn (User $user): array => $this->teacherData($user))->all(),
                'meta' => [
                    'current_page' => $teachers->currentPage(),
                    'from' => $teachers->firstItem(),
                    'last_page' => $teachers->lastPage(),
                    'per_page' => $teachers->perPage(),
                    'to' => $teachers->lastItem(),
                    'total' => $teachers->total(),
                ],
            ];
        });

        return response()->json($result);
    }

    public function store(StoreTeacherAccountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $expiresAt = now()->addHours(24);

        $teacher = DB::transaction(function () use ($data, $expiresAt): Teacher {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'teacher',
                'must_change_password' => true,
                'initial_password_expires_at' => $expiresAt,
            ]);

            return $user->teacher()->create([
                'employee_id' => $data['employee_id'],
                'contact_number' => $data['contact_number'] ?? null,
            ]);
        });

        $teacher->load('user');
        $this->invalidateTeacherListCache();
        $welcomeEmailSent = $this->sendInitialPasswordEmail($teacher->user, $data['password'], $expiresAt);

        return response()->json([
            'data' => [
                ...$this->teacherData($teacher->user),
                'welcome_email_sent' => $welcomeEmailSent,
                'initial_password_expires_at' => $expiresAt->toIso8601String(),
            ],
        ], 201);
    }

    public function update(UpdateTeacherAccountRequest $request, User $teacher): JsonResponse
    {
        abort_unless($teacher->isTeacher() && $teacher->teacher()->exists(), 404);
        $data = $request->validated();

        DB::transaction(function () use ($teacher, $data): void {
            $teacher->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
            $teacher->teacher()->update([
                'employee_id' => $data['employee_id'],
                'contact_number' => $data['contact_number'] ?? null,
            ]);
        });

        $teacher->load('teacher');
        $this->invalidateTeacherListCache();

        return response()->json(['data' => $this->teacherData($teacher)]);
    }

    public function resetPassword(ResetTeacherPasswordRequest $request, User $teacher): JsonResponse
    {
        $expiresAt = now()->addHours(24);
        $password = $request->validated()['password'];
        $teacher->update([
            'password' => $password,
            'must_change_password' => true,
            'initial_password_expires_at' => $expiresAt,
        ]);
        $welcomeEmailSent = $this->sendInitialPasswordEmail($teacher, $password, $expiresAt, true);

        return response()->json(['data' => [
            'message' => 'Teacher password reset successfully.',
            'welcome_email_sent' => $welcomeEmailSent,
            'initial_password_expires_at' => $expiresAt->toIso8601String(),
        ]]);
    }

    private function teacherData(User $user): array
    {
        $profile = $user->teacher;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $profile?->employee_id,
            'contact_number' => $profile?->contact_number,
        ];
    }

    private function invalidateTeacherListCache(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, (string) Str::uuid());
    }

    private function sendInitialPasswordEmail(
        User $teacher,
        string $password,
        CarbonInterface $expiresAt,
        bool $isPasswordReset = false,
    ): bool {
        try {
            Mail::mailer('smtp')->to($teacher->email)->send(new TeacherWelcomeMail(
                teacherName: $teacher->name,
                email: $teacher->email,
                initialPassword: $password,
                expiresAt: $expiresAt->format('F j, Y g:i A T'),
                isPasswordReset: $isPasswordReset,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return true;
    }
}
