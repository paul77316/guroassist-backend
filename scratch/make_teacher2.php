<?php
use App\Models\{User, Teacher, ClassAssignment};
use Illuminate\Support\Facades\Hash;

$u = User::firstOrCreate(
    ['email' => 'teacher2@demo.local'],
    ['name' => 'Second Teacher', 'password' => Hash::make('password'), 'role' => 'teacher']
);

$t = Teacher::firstOrCreate(
    ['user_id' => $u->id],
    ['employee_id' => 'DEMO-T2']
);

echo "user {$u->id}, teacher {$t->id}, owns classes: ",
    ClassAssignment::where('teacher_id', $t->id)->count(), "\n";
echo "First teacher id: ", Teacher::orderBy('id')->first()->id, "\n";
