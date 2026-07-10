<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessProjectMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_users_including_admins_as_assignable(): void
    {
        User::factory()->count(3)->create(['role' => 'employee']);
        $admin = User::factory()->create(['role' => 'admin']);

        $results = UserAccess::scopeAssignableProjectMembers(User::query(), $admin)->get();

        $this->assertCount(4, $results);
        $this->assertTrue($results->contains('id', $admin->id));
    }

    public function test_project_admin_sees_all_users_as_assignable(): void
    {
        User::factory()->count(2)->create(['role' => 'employee']);
        $projectAdmin = User::factory()->create(['role' => 'project_admin']);

        $results = UserAccess::scopeAssignableProjectMembers(User::query(), $projectAdmin)->get();

        $this->assertCount(3, $results);
    }

    public function test_project_manager_sees_the_employee_directory_as_assignable(): void
    {
        User::factory()->count(3)->create(['role' => 'employee']);
        $pm = User::factory()->create(['role' => 'project_manager']);

        $results = UserAccess::scopeAssignableProjectMembers(User::query(), $pm)->get();

        $this->assertCount(4, $results);
    }

    public function test_program_manager_sees_the_employee_directory_as_assignable(): void
    {
        User::factory()->count(3)->create(['role' => 'employee']);
        $programManager = User::factory()->create(['role' => 'program_manager']);

        $results = UserAccess::scopeAssignableProjectMembers(User::query(), $programManager)->get();

        $this->assertCount(4, $results);
    }

    public function test_employee_cannot_see_any_assignable_members(): void
    {
        User::factory()->count(3)->create(['role' => 'employee']);
        $employee = User::factory()->create(['role' => 'employee']);

        $results = UserAccess::scopeAssignableProjectMembers(User::query(), $employee)->get();

        $this->assertCount(0, $results);
    }

    public function test_null_actor_sees_no_assignable_members(): void
    {
        User::factory()->count(3)->create(['role' => 'employee']);

        $results = UserAccess::scopeAssignableProjectMembers(User::query(), null)->get();

        $this->assertCount(0, $results);
    }
}
