<?php

namespace Tests\Unit;

use App\Support\AssignmentStatus as S;
use PHPUnit\Framework\TestCase;

class AssignmentStatusTest extends TestCase
{
    public function test_not_yet_before_and_on_the_due_date_missing_the_day_after(): void
    {
        $this->assertSame(S::NOT_YET, S::resolve(null, '2030-03-05', '2030-03-04', null));
        $this->assertSame(S::NOT_YET, S::resolve(null, '2030-03-05', '2030-03-05', null)); // the due date itself
        $this->assertSame(S::MISSING, S::resolve(null, '2030-03-05', '2030-03-06', null));
    }

    public function test_a_stored_record_decides_the_status(): void
    {
        $this->assertSame(S::EXCUSED, S::resolve(S::EXCUSED, '2030-03-05', '2030-04-01', null));
        $this->assertSame(S::SUBMITTED, S::resolve(S::SUBMITTED, '2030-03-05', '2030-04-01', null));
        $this->assertSame(S::LATE, S::resolve(S::LATE, '2030-03-05', '2030-04-01', null));
    }

    public function test_joining_after_the_due_date_makes_it_not_applicable(): void
    {
        $this->assertSame(S::NOT_APPLICABLE, S::resolve(null, '2030-03-05', '2030-03-10', '2030-03-06'));
        // joined on the due date itself: the assignment still applies
        $this->assertSame(S::MISSING, S::resolve(null, '2030-03-05', '2030-03-10', '2030-03-05'));
        $this->assertSame(S::MISSING, S::resolve(null, '2030-03-05', '2030-03-10', '2030-02-01'));
    }

    public function test_it_only_applies_when_the_student_was_added_after_the_assignment_was_entered_too(): void
    {
        // added 6 March, due 5 March, assignment entered 4 March: they were not in the class yet
        $this->assertSame(S::NOT_APPLICABLE, S::resolve(null, '2030-03-05', '2030-03-10', '2030-03-06', '2030-03-04'));
        // assignment entered on 10 March with a back-dated due date: the student was already added
        $this->assertSame(S::MISSING, S::resolve(null, '2030-03-05', '2030-03-10', '2030-03-06', '2030-03-10'));
        // added on the same day the assignment was entered: counted
        $this->assertSame(S::MISSING, S::resolve(null, '2030-03-05', '2030-03-10', '2030-03-10', '2030-03-10'));
    }

    public function test_a_record_still_counts_for_a_student_who_joined_late(): void
    {
        $this->assertSame(S::LATE, S::resolve(S::LATE, '2030-03-05', '2030-03-10', '2030-03-08'));
    }

    public function test_hand_in_date_decides_on_time_or_late(): void
    {
        $this->assertSame(S::SUBMITTED, S::forDate('2030-03-04', '2030-03-05'));
        $this->assertSame(S::SUBMITTED, S::forDate('2030-03-05', '2030-03-05')); // on the due date
        $this->assertSame(S::LATE, S::forDate('2030-03-06', '2030-03-05'));
    }
}
