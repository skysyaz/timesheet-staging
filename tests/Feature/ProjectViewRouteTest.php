<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectViewRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_view_route_is_registered(): void
    {
        $pm = User::factory()->projectManager()->create();
        $project = Project::create([
            'code' => 'PRJ',
            'name' => 'Demo',
            'project_manager_id' => $pm->id,
            'program_manager_id' => User::factory()->programManager()->create()->id,
            'project_type_id' => 1,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('filament.admin.resources.projects.view'),
        );

        $url = ProjectResource::getUrl('view', ['record' => $project]);

        $this->actingAs($pm)
            ->get($url)
            ->assertOk()
            ->assertSee('Demo');
    }
}
