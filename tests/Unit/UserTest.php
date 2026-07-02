<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $user = new User(['role' => 'admin']);
        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_employee(): void
    {
        $user = new User(['role' => 'employee']);
        $this->assertFalse($user->isAdmin());
    }

    public function test_is_employee_returns_true(): void
    {
        $user = new User(['role' => 'employee']);
        $this->assertTrue($user->isEmployee());
    }

    public function test_is_approver_returns_true_for_pm(): void
    {
        $user = new User(['role' => 'project_manager']);
        $this->assertTrue($user->isApprover());
    }

    public function test_can_approve_as_pm(): void
    {
        $pm = new User(['role' => 'project_manager']);
        $admin = new User(['role' => 'admin']);
        $emp = new User(['role' => 'employee']);

        $this->assertTrue($pm->canApproveAsPm());
        $this->assertTrue($admin->canApproveAsPm());
        $this->assertFalse($emp->canApproveAsPm());
    }

    public function test_can_approve_as_program_manager(): void
    {
        $programManager = new User(['role' => 'program_manager']);
        $admin = new User(['role' => 'admin']);
        $pm = new User(['role' => 'project_manager']);

        $this->assertTrue($programManager->canApproveAsProgramManager());
        $this->assertTrue($admin->canApproveAsProgramManager());
        $this->assertFalse($pm->canApproveAsProgramManager());
    }
}
