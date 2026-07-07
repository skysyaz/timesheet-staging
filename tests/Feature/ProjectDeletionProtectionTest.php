<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDeletionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_project_preserves_timesheets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create();
        $project = Project::create([
            'code' => 'KEEP-01',
            'name' => 'Keep Timesheets',
            'status' => 'active',
        ]);
        $project->members()->attach($employee->id, ['assigned_role' => 'Developer']);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => '2026-06-22',
            'hours' => [8, 8, 0, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($admin);

        $project->delete();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('timesheets', ['id' => $timesheet->id, 'project_id' => $project->id]);
        $this->assertSame('Keep Timesheets', $timesheet->fresh()->project?->name);
    }

    public function test_trashed_project_can_be_restored(): void
    {
        $project = Project::create([
            'code' => 'REST-01',
            'name' => 'Restore Me',
            'status' => 'active',
        ]);

        $project->delete();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);

        $project->restore();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }

    public function test_force_delete_is_blocked_when_timesheets_exist(): void
    {
        $employee = User::factory()->create();
        $project = Project::create([
            'code' => 'BLOCK-01',
            'name' => 'Blocked Delete',
            'status' => 'active',
        ]);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => '2026-06-22',
            'hours' => [4, 0, 0, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        $project->delete();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $project->forceDelete();
    }

    public function test_force_delete_succeeds_when_no_timesheets_exist(): void
    {
        $project = Project::create([
            'code' => 'EMPTY-01',
            'name' => 'Empty Project',
            'status' => 'active',
        ]);

        $project->delete();
        $project->forceDelete();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
