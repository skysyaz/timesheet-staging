<?php

namespace Tests\Unit;

use App\Models\Timesheet;
use App\Models\User;
use App\Support\TimesheetAccess;
use PHPUnit\Framework\TestCase;

class TimesheetAccessEditTest extends TestCase
{
    public function test_employee_can_edit_own_draft(): void
    {
        $employee = new User(['role' => 'employee']);
        $employee->id = 1;
        $timesheet = new Timesheet(['user_id' => 1, 'status' => 'draft']);

        $this->assertTrue(TimesheetAccess::userCanEditTimesheet($employee, $timesheet));
    }

    public function test_employee_can_edit_own_rejected_timesheet(): void
    {
        $employee = new User(['role' => 'employee']);
        $employee->id = 1;
        $timesheet = new Timesheet(['user_id' => 1, 'status' => 'rejected']);

        $this->assertTrue(TimesheetAccess::userCanEditTimesheet($employee, $timesheet));
    }

    public function test_employee_cannot_edit_approved_timesheet(): void
    {
        $employee = new User(['role' => 'employee']);
        $employee->id = 1;
        $timesheet = new Timesheet(['user_id' => 1, 'status' => 'approved']);

        $this->assertFalse(TimesheetAccess::userCanEditTimesheet($employee, $timesheet));
    }

    public function test_employee_cannot_edit_other_users_draft(): void
    {
        $employee = new User(['role' => 'employee']);
        $employee->id = 1;
        $timesheet = new Timesheet(['user_id' => 2, 'status' => 'draft']);

        $this->assertFalse(TimesheetAccess::userCanEditTimesheet($employee, $timesheet));
    }

    public function test_manager_cannot_edit_draft_timesheet(): void
    {
        $pm = new User(['id' => 3, 'role' => 'project_manager']);
        $timesheet = new Timesheet(['user_id' => 1, 'status' => 'draft']);

        $this->assertFalse(TimesheetAccess::userCanEditTimesheet($pm, $timesheet));
    }

    public function test_admin_can_edit_any_editable_timesheet(): void
    {
        $admin = new User(['id' => 9, 'role' => 'admin']);
        $timesheet = new Timesheet(['user_id' => 1, 'status' => 'draft']);

        $this->assertTrue(TimesheetAccess::userCanEditTimesheet($admin, $timesheet));
    }

    public function test_admin_can_revert_approved_timesheet(): void
    {
        $admin = new User(['id' => 9, 'role' => 'admin']);
        $timesheet = new Timesheet(['status' => 'approved']);

        $this->assertTrue(TimesheetAccess::userCanRevertToDraft($admin, $timesheet));
    }

    public function test_non_admin_cannot_revert_approved_timesheet(): void
    {
        $employee = new User(['id' => 1, 'role' => 'employee']);
        $timesheet = new Timesheet(['status' => 'approved']);

        $this->assertFalse(TimesheetAccess::userCanRevertToDraft($employee, $timesheet));
    }
}
