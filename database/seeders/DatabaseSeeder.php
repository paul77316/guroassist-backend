<?php 
namespace Database\Seeders; 
use App\Models\AcademicYear; 
use App\Models\ClassAssignment; 
use App\Models\Enrollment; 
use App\Models\GradeLevel; 
use App\Models\Section; 
use App\Models\Student; 
use App\Models\Subject; 
use App\Models\Teacher; 
use App\Models\User; 
use Illuminate\Database\Seeder; 

class DatabaseSeeder extends Seeder { 
   /** * SEED / DEMO DATA ONLY — not for production use. */
    public function run(): void { 
      $teacherUser = User::create([ 'name' => 'Demo Teacher', 'email' => 'teacher@demo.local', 'password' => 'password', 'role' => 'teacher', ]); 
      $teacher = Teacher::create([ 'user_id' => $teacherUser->id, 'employee_id' => 'DEMO-0001', 'contact_number' => '09170000000', ]); 
      User::create([ 'name' => 'Demo Admin', 'email' => 'admin@demo.local', 'password' => 'password', 'role' => 'admin', ]); 
      $year = AcademicYear::create([ 'name' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'is_active' => true, ]); 
      $grade8 = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]); 
      $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']); 
      $section = Section::create([ 'name' => 'Mabini', 'grade_level_id' => 
      $grade8->id, 'academic_year_id' => $year->id, 'adviser_id' => $teacher->id, ]);

      ClassAssignment::create([ 'teacher_id' => $teacher->id, 'subject_id' => $math->id, 'section_id' => $section->id, 'academic_year_id' => $year->id, ]);
      $students = [
          ['student_id' => '2026-0001', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'sex' => 'male', 'date_of_birth' => '2013-01-04'],
          ['student_id' => '2026-0002', 'first_name' => 'Maria', 'last_name' => 'Santos', 'sex' => 'female', 'date_of_birth' => '2013-03-15'],
      ];
      foreach ($students as $data) {
          $student = Student::create($data);
          Enrollment::create([
              'student_id' => $student->id,
              'section_id' => $section->id,
              'academic_year_id' => $year->id,
          ]);
      }
  }
}