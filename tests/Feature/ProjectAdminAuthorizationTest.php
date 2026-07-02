<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\UserResource;
use App\Models\Project;
use App\Models\User;
use App\Support\TimesheetAccess;
use App\Support\UserAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_admin_can_create_project(): void
    {
        $projectAdmin = User::factory()->projectAdmin()->create();

        $this->actingAs($projectAdmin);

        $this->assertTrue($projectAdmin->isProjectAdmin());
        $this->assertTrue(TimesheetAccess::userCanEditProject($projectAdmin, new Project()));
        $this->assertTrue(ProjectResource::canCreate());
    }

    public function test_project_admin_sees_users_menu_and_create_form_role_dropdown_excludes_admin(): void
    {
        $projectAdmin = User::factory()->projectAdmin()->create();

        $options = UserAccess::assignableRoleOptions($projectAdmin);

        $this->assertTrue($projectAdmin->canManageUsers());
        $this->assertArrayHasKey('employee', $options);
        $this->assertArrayHasKey('program_manager', $options);
        $this->assertArrayNotHasKey('admin', $options);

        $this->actingAs($projectAdmin)
            ->get(UserResource::getUrl('index'))
            ->assertOk();

        $this->actingAs($projectAdmin)
            ->get(UserResource::getUrl('create'))
            ->assertOk();
    }

    public function test_project_admin_cannot_access_admin_user_edit(): void
    {
        $projectAdmin = User::factory()->projectAdmin()->create();
        $admin = User::factory()->admin()->create();

        $this->assertFalse(UserAccess::canEditUser($projectAdmin, $admin));

        $this->actingAs($projectAdmin)
            ->get(UserResource::getUrl('edit', ['record' => $admin]))
            ->assertForbidden();
    }

    public function test_employee_cannot_create_projects(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee);

        $this->assertFalse(ProjectResource::canCreate());
    }
}
