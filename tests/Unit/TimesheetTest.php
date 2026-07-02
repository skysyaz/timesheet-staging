<?php

namespace Tests\Unit;

use App\Models\Timesheet;
use PHPUnit\Framework\TestCase;

class TimesheetTest extends TestCase
{
    public function test_total_hours_with_full_week(): void
    {
        $ts = new Timesheet(['hours' => [8, 8, 8, 8, 8, 0, 0]]);
        $this->assertEquals(40, $ts->totalHours());
    }

    public function test_total_hours_with_empty_week(): void
    {
        $ts = new Timesheet(['hours' => [0, 0, 0, 0, 0, 0, 0]]);
        $this->assertEquals(0, $ts->totalHours());
    }

    public function test_total_hours_with_null_hours(): void
    {
        $ts = new Timesheet();
        $this->assertEquals(0, $ts->totalHours());
    }

    public function test_total_hours_with_partial_week(): void
    {
        $ts = new Timesheet(['hours' => [8, 7.5, 8, 0, 0, 0, 0]]);
        $this->assertEquals(23.5, $ts->totalHours());
    }

    public function test_task_for_day_returns_daily_task(): void
    {
        $ts = new Timesheet([
            'hours' => [8, 8, 0, 0, 0, 0, 0],
            'tasks' => ['Site inspection', 'Client meeting', '', '', '', '', ''],
        ]);

        $this->assertSame('Site inspection', $ts->taskForDay(0));
        $this->assertSame('Client meeting', $ts->taskForDay(1));
        $this->assertSame('', $ts->taskForDay(2));
    }

    public function test_task_for_day_falls_back_to_notes_when_hours_logged(): void
    {
        $ts = new Timesheet([
            'hours' => [8, 0, 0, 0, 0, 0, 0],
            'tasks' => ['', '', '', '', '', '', ''],
            'notes' => 'General project work',
        ]);

        $this->assertSame('General project work', $ts->taskForDay(0));
        $this->assertSame('', $ts->taskForDay(1));
    }

    public function test_is_draft(): void
    {
        $ts = new Timesheet(['status' => 'draft']);
        $this->assertTrue($ts->isDraft());
        $this->assertFalse($ts->isApproved());
    }

    public function test_is_pending_pm(): void
    {
        $ts = new Timesheet(['status' => 'pending_pm']);
        $this->assertTrue($ts->isPendingPm());
    }

    public function test_is_pending_program_manager(): void
    {
        $ts = new Timesheet(['status' => 'pending_program_manager']);
        $this->assertTrue($ts->isPendingProgramManager());
    }

    public function test_is_approved(): void
    {
        $ts = new Timesheet(['status' => 'approved']);
        $this->assertTrue($ts->isApproved());
    }

    public function test_is_rejected(): void
    {
        $ts = new Timesheet(['status' => 'rejected']);
        $this->assertTrue($ts->isRejected());
    }

    public function test_is_editable_returns_true_for_draft_and_rejected(): void
    {
        $draft = new Timesheet(['status' => 'draft']);
        $rejected = new Timesheet(['status' => 'rejected']);
        $approved = new Timesheet(['status' => 'approved']);

        $this->assertTrue($draft->isEditable());
        $this->assertTrue($rejected->isEditable());
        $this->assertFalse($approved->isEditable());
    }

    public function test_is_submittable_returns_true_for_draft_and_rejected(): void
    {
        $draft = new Timesheet(['status' => 'draft']);
        $rejected = new Timesheet(['status' => 'rejected']);
        $pending = new Timesheet(['status' => 'pending_pm']);

        $this->assertTrue($draft->isSubmittable());
        $this->assertTrue($rejected->isSubmittable());
        $this->assertFalse($pending->isSubmittable());
    }
}
