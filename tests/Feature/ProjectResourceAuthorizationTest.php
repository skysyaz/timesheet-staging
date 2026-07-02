<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_manager_sees_all_projects(): void
    {
        $pm = User::factory()->projectManager()->create();

        Project::create([
            'code' => 'A',
            'name' => 'Assigned',
            'project_manager_id' => $pm->id,
            'program_manager_id' => User::factory()->programManager()->create()->id,
            'created_by' => $pm->id,
        ]);

        Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
            'program_manager_id' => User::factory()->programManager()->create()->id,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($pm);

        $this->assertCount(2, ProjectResource::getEloquentQuery()->get());
    }

    public function test_project_manager_can_edit_assigned_project_even_when_created_by_someone_else(): void
    {
        $pm = User::factory()->projectManager()->create();
        $other = Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_manager_id' => $pm->id,
            'program_manager_id' => User::factory()->programManager()->create()->id,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($pm);

        $this->assertTrue(ProjectResource::canView($other));
        $this->assertTrue(ProjectResource::canEdit($other));
    }

    public function test_program_manager_can_edit_assigned_project(): void
    {
        $programManager = User::factory()->programManager()->create();
        $project = Project::create([
            'code' => 'C',
            'name' => 'Directed',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
            'program_manager_id' => $programManager->id,
            'project_type_id' => 1,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($programManager);

        $this->assertTrue(ProjectResource::canEdit($project));
    }

    public function test_project_manager_cannot_edit_unassigned_project(): void
    {
        $pm = User::factory()->projectManager()->create();
        $unassigned = Project::create([
            'code' => 'D',
            'name' => 'Unassigned',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
            'program_manager_id' => User::factory()->programManager()->create()->id,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($pm);

        $this->assertTrue(ProjectResource::canView($unassigned));
        $this->assertFalse(ProjectResource::canEdit($unassigned));
    }

    public function test_project_manager_can_edit_project_they_created(): void
    {
        $pm = User::factory()->projectManager()->create();
        $own = Project::create([
            'code' => 'A',
            'name' => 'Mine',
            'project_manager_id' => $pm->id,
            'program_manager_id' => User::factory()->programManager()->create()->id,
            'created_by' => $pm->id,
        ]);

        $this->actingAs($pm);

        $this->assertTrue(ProjectResource::canEdit($own));
    }

    public function test_project_manager_can_create_projects(): void
    {
        $this->actingAs(User::factory()->projectManager()->create());

        $this->assertTrue(ProjectResource::canCreate());
    }

    public function test_program_manager_can_create_projects(): void
    {
        $this->actingAs(User::factory()->programManager()->create());

        $this->assertTrue(ProjectResource::canCreate());
    }

    public function test_admin_can_edit_any_project(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::create([
            'code' => 'A',
            'name' => 'Any',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
            'program_manager_id' => User::factory()->programManager()->create()->id,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($admin);

        $this->assertTrue(ProjectResource::canEdit($project));
        $this->assertTrue(ProjectResource::canView($project));
    }
}
