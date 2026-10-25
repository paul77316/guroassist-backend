<?php

namespace Tests\Feature;

use App\Jobs\SendGuardianAttendanceNotice;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceNotification;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\MonitoringSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2030-03-10';

    private const TZ = 'Asia/Manila';

    public function test_guests_are_rejected_by_both_attendance_endpoints(): void
    {
        ['class' => $class] = $this->makeAttendanceContext();

        $this->getJson('/api/attendance?class_assignment_id='.$class->id.'&date='.self::DATE)
            ->assertUnauthorized();
    }

    public function test_roster_response_includes_active_students_and_saved_statuses(): void
    {
        ['owner' => $owner, 'class' => $class, 'students' => $students] = $this->makeAttendanceContext();
        $inactive = Student::create([
            'student_id' => 'INACTIVE-1',
            'first_name' => 'Inactive',
            'last_name' => 'Student',
            'sex' => 'female',
            'date_of_birth' => '2015-01-01',
            'status' => 'archived',
        ]);
        Enrollment::create([
            'student_id' => $inactive->id,
            'class_assignment_id' => $class->id,
            'section_id' => $class->section_id,
            'academic_year_id' => $class->academic_year_id,
        ]);
        Attendance::create([
            'student_id' => $students[0]->id,
            'class_assignment_id' => $class->id,
            'attendance_date' => self::DATE,
            'status' => 'late',
            'remarks' => 'Arrived by bus',
            'recorded_by' => $owner->id,
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/attendance?class_assignment_id='.$class->id.'&date='.self::DATE)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'class_assignment_id' => $class->id,
                    'date' => self::DATE,
                    'students' => [
                        [
                            'student_id' => $students[0]->id,
                            'full_name' => 'Avery Active',
                            'status' => 'late',
                            'remarks' => 'Arrived by bus',
                            'version' => 1,
                        ],
                        [
                            'student_id' => $students[1]->id,
                            'full_name' => 'Casey Active',
                            'status' => null,
                            'remarks' => null,
                            'version' => 0,
                        ],
                    ],
                ],
            ]);
    }

    public function test_only_the_class_teacher_or_an_admin_can_access_attendance(): void
    {
        ['owner' => $owner, 'other' => $other, 'admin' => $admin, 'class' => $class, 'classOther' => $classOther, 'students' => $students] = $this->makeAttendanceContext();
        $url = '/api/attendance?class_assignment_id='.$class->id.'&date='.self::DATE;
        $payload = [
            'class_assignment_id' => $class->id,
            'date' => self::DATE,
            'records' => [['student_id' => $students[0]->id, 'status' => 'present']],
        ];

        Sanctum::actingAs($other);
        $this->getJson($url)->assertForbidden();

        Sanctum::actingAs($owner);
        $this->getJson('/api/attendance?class_assignment_id='.$classOther->id.'&date='.self::DATE)
            ->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson($url)->assertOk();
    }




    public function test_archived_classes_return_not_found_and_keep_existing_attendance(): void
    {
        ['owner' => $owner, 'admin' => $admin, 'class' => $class, 'students' => $students] = $this->makeAttendanceContext();
        Attendance::create([
            'student_id' => $students[0]->id,
            'class_assignment_id' => $class->id,
            'attendance_date' => self::DATE,
            'status' => 'present',
            'recorded_by' => $owner->id,
        ]);
        $class->update(['status' => 'archived']);
        $class->delete();
        $url = '/api/attendance?class_assignment_id='.$class->id.'&date='.self::DATE;

        Sanctum::actingAs($owner);
        $this->getJson($url)->assertNotFound();
        $this->postJson('/api/attendance/bulk', [
            'class_assignment_id' => $class->id,
            'date' => self::DATE,
            'records' => [['student_id' => $students[1]->id, 'status' => 'absent']],
        ])->assertNotFound();

        Sanctum::actingAs($admin);
        $this->getJson($url)->assertNotFound();

        $this->assertDatabaseHas('attendance', [
            'student_id' => $students[0]->id,
            'class_assignment_id' => $class->id,
            'attendance_date' => self::DATE,
            'status' => 'present',
        ]);
        $this->assertDatabaseMissing('attendance', [
            'student_id' => $students[1]->id,
            'class_assignment_id' => $class->id,
            'attendance_date' => self::DATE,
        ]);
    }




    /** @return array{owner: User, other: User, admin: User, class: ClassAssignment, classOther: ClassAssignment, students: array{Student, Student}} */
    private function makeAttendanceContext(): array
    {
        $owner = $this->makeUser('teacher', 'owner@test.local');
        $other = $this->makeUser('teacher', 'other@test.local');
        $admin = $this->makeUser('admin', 'admin@test.local');
        $ownerTeacher = Teacher::create(['user_id' => $owner->id, 'employee_id' => 'T-OWNER']);
        $otherTeacher = Teacher::create(['user_id' => $other->id, 'employee_id' => 'T-OTHER']);

        $year = AcademicYear::create([
            'name' => '2030-2031',
            'start_date' => '2030-01-01',
            'end_date' => '2030-12-31',
            'is_active' => true,
        ]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $science = Subject::create(['name' => 'Science', 'code' => 'SCI']);
        $section = Section::create([
            'name' => 'Mabini',
            'grade_level_id' => $grade->id,
            'academic_year_id' => $year->id,
        ]);

        $class = ClassAssignment::create([
            'teacher_id' => $ownerTeacher->id,
            'subject_id' => $math->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);
        $classOther = ClassAssignment::create([
            'teacher_id' => $otherTeacher->id,
            'subject_id' => $science->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);

        $students = [
            Student::create([
                'student_id' => 'ST-1',
                'first_name' => 'Avery',
                'last_name' => 'Active',
                'sex' => 'female',
                'date_of_birth' => '2015-01-01',
                'status' => 'active',
            ]),
            Student::create([
                'student_id' => 'ST-2',
                'first_name' => 'Casey',
                'last_name' => 'Active',
                'sex' => 'male',
                'date_of_birth' => '2015-01-01',
                'status' => 'active',
            ]),
        ];

        foreach ($students as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'class_assignment_id' => $class->id,
                'section_id' => $section->id,
                'academic_year_id' => $year->id,
            ]);
        }

        $this->travelTo(now(self::TZ)->setDate(2030, 3, 10)->setTime(10, 0));

        return compact('owner', 'other', 'admin', 'class', 'classOther', 'students');
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
