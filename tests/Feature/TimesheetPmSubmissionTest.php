<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource;
use App\Filament\Resources\TimesheetResource\Pages\ListTimesheets;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\TimesheetPendingDirectorNotification;
use App\Notifications\TimesheetSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TimesheetPmSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $pm;

    private User $pd;

    private Project $project;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pm = User::factory()->projectManager()->create();
        $this->pd = User::factory()->projectDirector()->create();
        $this->project = Project::create([
            'code' => 'PM-01',
            'name' => 'PM Project',
            'project_manager_id' => $this->pm->id,
            'project_director_id' => $this->pd->id,
        ]);
        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Setting::create(['key' => 'emailNotifications', 'value' => false]);
        Setting::create(['key' => 'requireDirectorApproval', 'value' => true]);
    }

    public function test_pm_submitted_timesheet_goes_to_pending_pd(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'draft',
        ]);

        $this->actingAs($this->pm);

        TimesheetResource::submitTimesheet($timesheet);

        $timesheet->refresh();

        $this->assertSame('pending_pd', $timesheet->status);
        $this->assertDatabaseHas('approval_logs', [
            'timesheet_id' => $timesheet->id,
            'user_id' => $this->pm->id,
            'action' => 'submitted',
        ]);
        $this->assertDatabaseHas('approval_logs', [
            'timesheet_id' => $timesheet->id,
            'user_id' => $this->pm->id,
            'action' => 'approved_pm',
        ]);
    }

    public function test_pm_submission_notifies_project_director_not_self(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'draft',
        ]);

        Setting::where('key', 'emailNotifications')->update(['value' => true]);

        $this->actingAs($this->pm);
        Notification::fake();

        TimesheetResource::submitTimesheet($timesheet);

        Notification::assertSentTo($this->pd, TimesheetPendingDirectorNotification::class);
        Notification::assertNotSentTo($this->pm, TimesheetSubmittedNotification::class);
    }

    public function test_pm_can_submit_multiple_timesheets_for_same_project(): void
    {
        $first = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [8, 0, 0, 0, 0, 0, 0],
            'tasks' => ['Activity 1', '', '', '', '', '', ''],
            'status' => 'draft',
        ]);

        $second = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [0, 8, 0, 0, 0, 0, 0],
            'tasks' => ['', 'Activity 2', '', '', '', '', ''],
            'status' => 'draft',
        ]);

        $this->actingAs($this->pm);

        TimesheetResource::submitTimesheet($first);
        TimesheetResource::submitTimesheet($second);

        $this->assertSame('pending_pd', $first->fresh()->status);
        $this->assertSame('pending_pd', $second->fresh()->status);
        $this->assertSame(2, Timesheet::query()->where('user_id', $this->pm->id)->where('project_id', $this->project->id)->count());
    }

    public function test_pm_can_submit_second_timesheet_from_list_action(): void
    {
        Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [8, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_pd',
        ]);

        $second = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [0, 8, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        Livewire::actingAs($this->pm)
            ->test(ListTimesheets::class)
            ->callTableAction('submit', $second)
            ->assertNotified();

        $this->assertSame('pending_pd', $second->fresh()->status);
        $this->assertSame('Project Manager', $second->fresh()->project_role);
    }
}
