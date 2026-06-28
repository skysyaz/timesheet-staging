<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_manager_can_create_project_with_timeline_and_members(): void
    {
        $pm = User::factory()->projectManager()->create();
        $pd = User::factory()->projectDirector()->create();
        $memberOne = User::factory()->create(['name' => 'Ainie Idris']);
        $memberTwo = User::factory()->create(['name' => 'Team Member']);

        $this->actingAs($pm);

        Livewire::test(\App\Filament\Resources\ProjectResource\Pages\CreateProject::class)
            ->fillForm([
                'code' => 'NEW01',
                'name' => 'New Project',
                'description' => 'Website redesign scope',
                'status' => 'active',
                'start_date' => '2026-06-22',
                'end_date' => '2026-06-28',
                'project_manager_id' => $pm->id,
                'project_director_id' => $pd->id,
                'member_assignments' => [
                    ['user_id' => $memberOne->id, 'assigned_role' => 'Designer'],
                    ['user_id' => $memberTwo->id, 'assigned_role' => 'Developer'],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();

        $project = Project::query()->where('code', 'NEW01')->firstOrFail();

        $this->assertDatabaseHas('projects', [
            'code' => 'NEW01',
            'name' => 'New Project',
            'description' => 'Website redesign scope',
            'created_by' => $pm->id,
        ]);
        $this->assertSame('2026-06-22', $project->start_date?->format('Y-m-d'));
        $this->assertSame('2026-06-28', $project->end_date?->format('Y-m-d'));

        $this->assertCount(2, $project->members);
        $this->assertTrue($project->hasMember($memberOne));
        $this->assertTrue($project->hasMember($memberTwo));
        $this->assertSame('Designer', $project->members()->whereKey($memberOne->id)->first()?->pivot?->assigned_role);
    }

    public function test_end_date_must_be_on_or_after_start_date(): void
    {
        $pm = User::factory()->projectManager()->create();
        $pd = User::factory()->projectDirector()->create();
        $member = User::factory()->create();

        $this->actingAs($pm);

        Livewire::test(\App\Filament\Resources\ProjectResource\Pages\CreateProject::class)
            ->fillForm([
                'code' => 'BAD01',
                'name' => 'Invalid Timeline',
                'status' => 'active',
                'start_date' => '2026-06-28',
                'end_date' => '2026-06-22',
                'project_manager_id' => $pm->id,
                'project_director_id' => $pd->id,
                'member_assignments' => [
                    ['user_id' => $member->id, 'assigned_role' => 'Developer'],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['end_date' => 'after_or_equal']);

        $this->assertDatabaseMissing('projects', ['code' => 'BAD01']);
    }

    public function test_project_requires_at_least_one_member(): void
    {
        $pm = User::factory()->projectManager()->create();
        $pd = User::factory()->projectDirector()->create();

        $this->actingAs($pm);

        Livewire::test(\App\Filament\Resources\ProjectResource\Pages\CreateProject::class)
            ->fillForm([
                'code' => 'NOMEM',
                'name' => 'No Members',
                'status' => 'active',
                'start_date' => '2026-06-22',
                'end_date' => '2026-06-28',
                'project_manager_id' => $pm->id,
                'project_director_id' => $pd->id,
                'member_assignments' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['member_assignments']);

        $this->assertDatabaseMissing('projects', ['code' => 'NOMEM']);
    }

    public function test_project_name_must_be_unique(): void
    {
        Project::create(['code' => 'EXIST', 'name' => 'Website Redesign']);
        $pm = User::factory()->projectManager()->create();
        $pd = User::factory()->projectDirector()->create();
        $member = User::factory()->create();

        $this->actingAs($pm);

        Livewire::test(\App\Filament\Resources\ProjectResource\Pages\CreateProject::class)
            ->fillForm([
                'code' => 'NEW99',
                'name' => 'Website Redesign',
                'status' => 'active',
                'start_date' => '2026-06-22',
                'end_date' => '2026-06-28',
                'project_manager_id' => $pm->id,
                'project_director_id' => $pd->id,
                'member_assignments' => [
                    ['user_id' => $member->id, 'assigned_role' => 'Developer'],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    public function test_created_project_redirects_to_view_page(): void
    {
        $pm = User::factory()->projectManager()->create();
        $pd = User::factory()->projectDirector()->create();
        $member = User::factory()->create();

        $this->actingAs($pm);

        Livewire::test(\App\Filament\Resources\ProjectResource\Pages\CreateProject::class)
            ->fillForm([
                'code' => 'VIEW1',
                'name' => 'View Redirect',
                'status' => 'active',
                'start_date' => '2026-06-22',
                'end_date' => '2026-06-28',
                'project_manager_id' => $pm->id,
                'project_director_id' => $pd->id,
                'member_assignments' => [
                    ['user_id' => $member->id, 'assigned_role' => 'Developer'],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(
                ProjectResource::getUrl('view', ['record' => Project::query()->where('code', 'VIEW1')->first()])
            );
    }
}
