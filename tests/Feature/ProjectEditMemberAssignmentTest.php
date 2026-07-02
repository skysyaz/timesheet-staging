<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectEditMemberAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_manager_can_add_members_when_editing_their_project(): void
    {
        $pm = User::factory()->projectManager()->create();
        $programManager = User::factory()->programManager()->create();
        $existingMember = User::factory()->create(['name' => 'Existing Member']);
        $newMember = User::factory()->create(['name' => 'New Member']);

        $project = Project::create([
            'code' => 'EDT-01',
            'name' => 'Edit Flow Project',
            'status' => 'active',
            'start_date' => '2026-06-22',
            'end_date' => '2026-06-28',
            'project_manager_id' => $pm->id,
            'program_manager_id' => $programManager->id,
        ]);
        $project->members()->attach($existingMember->id, ['assigned_role' => 'Developer']);

        $this->actingAs($pm);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm([
                'member_assignments' => [
                    ['user_id' => $existingMember->id, 'assigned_role' => 'Developer'],
                    ['user_id' => $newMember->id, 'assigned_role' => 'QA Engineer'],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();

        $this->assertCount(2, $project->members);
        $this->assertTrue($project->hasMember($newMember));
        $this->assertSame(
            'QA Engineer',
            $project->members()->whereKey($newMember->id)->first()?->pivot?->assigned_role,
        );
    }

    public function test_program_manager_can_add_members_when_editing_their_project(): void
    {
        $pm = User::factory()->projectManager()->create();
        $programManager = User::factory()->programManager()->create();
        $newMember = User::factory()->create(['name' => 'Fresh Recruit']);

        $project = Project::create([
            'code' => 'EDT-02',
            'name' => 'Program Manager Edit Project',
            'status' => 'active',
            'start_date' => '2026-06-22',
            'end_date' => '2026-06-28',
            'project_manager_id' => $pm->id,
            'program_manager_id' => $programManager->id,
        ]);

        $this->actingAs($programManager);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm([
                'member_assignments' => [
                    ['user_id' => $newMember->id, 'assigned_role' => 'Analyst'],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();

        $this->assertCount(1, $project->members);
        $this->assertTrue($project->hasMember($newMember));
    }
}
