<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\ClassroomSeatingLayout;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/** Seating layout config (web side). Needs a *_test MySQL database, like the other feature tests. */
class SeatingChartApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private User $admin;

    private ClassAssignment $class;

    private Student $a;

    private Student $b;

    private int $seq = 0;

    protected function beforeRefreshingDatabase(): void
    {
        $db = (string) config('database.connections.'.config('database.default').'.database');
        if (! str_ends_with($db, '_test')) {
            throw new RuntimeException("Refusing to wipe '$db'. Point phpunit.xml at a database whose name ends in _test.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@test.local', 'password' => 'password', 'role' => 'teacher']);
        $this->other = User::create(['name' => 'Other', 'email' => 'other@test.local', 'password' => 'password', 'role' => 'teacher']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin']);
        $ownerProfile = Teacher::create(['user_id' => $this->owner->id, 'employee_id' => 'T-OWNER']);
        Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-OTHER']);

        $year = AcademicYear::create(['name' => '2030-2031', 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $section = Section::create(['name' => 'Mabini', 'grade_level_id' => $grade->id, 'academic_year_id' => $year->id]);

        $this->class = ClassAssignment::create([
            'teacher_id' => $ownerProfile->id, 'subject_id' => $math->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active',
        ]);
        $this->a = $this->enrolled();
        $this->b = $this->enrolled();
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
        $this->putJson($this->url(), $this->body())->assertUnauthorized();
    }

    public function test_a_class_without_a_layout_returns_an_empty_default_with_its_roster(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson($this->url())->assertOk()
            ->assertJsonPath('data.version', 0)
            ->assertJsonPath('data.rows', 5)
            ->assertJsonCount(25, 'data.cells')
            ->assertJsonCount(2, 'data.students');
    }

    public function test_a_teacher_saves_and_reloads_a_layout(): void
    {
        Sanctum::actingAs($this->owner);

        $this->putJson($this->url(), $this->body(cells: [$this->a->id, null, null, $this->b->id]))
            ->assertOk()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.cells.0', $this->a->id)
            ->assertJsonPath('data.cells.3', $this->b->id);

        $this->getJson($this->url())->assertJsonPath('data.version', 1)->assertJsonPath('data.rows', 2);
        $this->assertSame(1, AuditLog::where('event', 'seating.saved')->count());
    }

    public function test_each_save_moves_the_version_and_a_stale_save_is_refused(): void
    {
        Sanctum::actingAs($this->owner);
        $this->putJson($this->url(), $this->body(cells: [$this->a->id, null, null, null]))->assertOk();
        $this->putJson($this->url(), $this->body(version: 1, cells: [null, $this->a->id, null, null]))
            ->assertOk()->assertJsonPath('data.version', 2);

        $this->putJson($this->url(), $this->body(version: 1, cells: [null, null, $this->a->id, null]))
            ->assertStatus(409)->assertJsonPath('current_version', 2);

        $this->assertSame([null, $this->a->id, null, null], ClassroomSeatingLayout::firstOrFail()->cells);
    }

    public function test_changing_seats_never_touches_attendance_history(): void
    {
        Attendance::create([
            'student_id' => $this->a->id, 'class_assignment_id' => $this->class->id,
            'attendance_date' => '2030-03-01', 'status' => 'late', 'sync_version' => 1,
        ]);
        Sanctum::actingAs($this->owner);

        $this->putJson($this->url(), $this->body(cells: [$this->a->id, null, null, null]))->assertOk();
        $this->putJson($this->url(), $this->body(version: 1, cells: [null, null, null, $this->a->id]))->assertOk();

        $row = Attendance::firstOrFail();
        $this->assertSame('late', $row->status);
        $this->assertSame(1, $row->sync_version);
    }

    public function test_invalid_layouts_are_refused(): void
    {
        $stranger = Student::create([
            'student_id' => 'N-X', 'first_name' => 'Not', 'last_name' => 'Enrolled',
            'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active',
        ]);
        Sanctum::actingAs($this->owner);

        $this->putJson($this->url(), $this->body(cells: [$this->a->id, $this->a->id, null, null]))->assertUnprocessable();
        $this->putJson($this->url(), $this->body(cells: [$stranger->id, null, null, null]))->assertUnprocessable();
        $this->putJson($this->url(), $this->body(cells: [$this->a->id, null, null]))->assertUnprocessable();
        $this->putJson($this->url(), $this->body(rows: 13, cells: array_fill(0, 26, null)))->assertUnprocessable();

        $this->assertSame(0, ClassroomSeatingLayout::count());
    }

    public function test_other_teachers_cannot_read_or_change_the_layout_but_admins_can(): void
    {
        Sanctum::actingAs($this->other);
        $this->getJson($this->url())->assertForbidden();
        $this->putJson($this->url(), $this->body())->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->putJson($this->url(), $this->body(cells: [$this->a->id, null, null, null]))->assertOk();
    }

    public function test_an_archived_class_cannot_be_reseated(): void
    {
        $this->class->update(['status' => 'archived']);
        Sanctum::actingAs($this->owner);

        $this->putJson($this->url(), $this->body())->assertUnprocessable();
        $this->assertSame(0, ClassroomSeatingLayout::count());
    }

    private function url(): string
    {
        return "/api/class-assignments/{$this->class->id}/seating-layout";
    }

    /** @return array<string, mixed> */
    private function body(int $version = 0, int $rows = 2, int $cols = 2, ?array $cells = null): array
    {
        return ['version' => $version, 'rows' => $rows, 'cols' => $cols, 'cells' => $cells ?? [null, null, null, null]];
    }

    private function enrolled(): Student
    {
        $student = Student::create([
            'student_id' => 'N-'.(++$this->seq), 'first_name' => 'Student'.$this->seq, 'last_name' => 'Test',
            'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student->id, 'class_assignment_id' => $this->class->id,
            'section_id' => $this->class->section_id, 'academic_year_id' => $this->class->academic_year_id,
        ]);

        return $student;
    }
}
