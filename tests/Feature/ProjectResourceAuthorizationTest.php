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
            'project_director_id' => User::factory()->projectDirector()->create()->id,
            'created_by' => $pm->id,
        ]);

        Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
            'project_director_id' => User::factory()->projectDirector()->create()->id,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($pm);

        $this->assertCount(2, ProjectResource::getEloquentQuery()->get());
    }

    public function test_project_manager_can_view_but_not_edit_project_created_by_someone_else(): void
    {
        $pm = User::factory()->projectManager()->create();
        $other = Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_manager_id' => $pm->id,
            'project_director_id' => User::factory()->projectDirector()->create()->id,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($pm);

        $this->assertTrue(ProjectResource::canView($other));
        $this->assertFalse(ProjectResource::canEdit($other));
    }

    public function test_project_manager_can_edit_project_they_created(): void
    {
        $pm = User::factory()->projectManager()->create();
        $own = Project::create([
            'code' => 'A',
            'name' => 'Mine',
            'project_manager_id' => $pm->id,
            'project_director_id' => User::factory()->projectDirector()->create()->id,
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

    public function test_project_director_can_create_projects(): void
    {
        $this->actingAs(User::factory()->projectDirector()->create());

        $this->assertTrue(ProjectResource::canCreate());
    }

    public function test_admin_can_edit_any_project(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::create([
            'code' => 'A',
            'name' => 'Any',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
            'project_director_id' => User::factory()->projectDirector()->create()->id,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($admin);

        $this->assertTrue(ProjectResource::canEdit($project));
        $this->assertTrue(ProjectResource::canView($project));
    }
}
