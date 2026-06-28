<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\TimesheetApprovedNotification;
use App\Notifications\TimesheetPendingDirectorNotification;
use App\Notifications\TimesheetRejectedNotification;
use App\Notifications\TimesheetSubmittedNotification;
use App\Support\TimesheetNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TimesheetNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private User $pm;

    private User $otherPm;

    private User $pd;

    private User $admin;

    private Project $project;

    private Timesheet $timesheet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create(['role' => 'employee']);
        $this->pm = User::factory()->projectManager()->create();
        $this->otherPm = User::factory()->projectManager()->create();
        $this->pd = User::factory()->projectDirector()->create();
        $this->admin = User::factory()->admin()->create();
        $this->project = Project::create([
            'code' => 'TEST-01',
            'name' => 'Test Project',
            'project_manager_id' => $this->pm->id,
            'project_director_id' => $this->pd->id,
        ]);

        $this->timesheet = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'pending_pm',
        ]);

        Setting::create(['key' => 'emailNotifications', 'value' => true]);
        Setting::create(['key' => 'requireDirectorApproval', 'value' => true]);
    }

    public function test_submission_notifies_assigned_project_manager_only(): void
    {
        Notification::fake();

        TimesheetNotifier::notifySubmitted($this->timesheet);

        Notification::assertSentTo($this->pm, TimesheetSubmittedNotification::class);
        Notification::assertNotSentTo($this->otherPm, TimesheetSubmittedNotification::class);
        Notification::assertNotSentTo($this->admin, TimesheetSubmittedNotification::class);
        Notification::assertNotSentTo($this->employee, TimesheetSubmittedNotification::class);
    }

    public function test_submission_notifies_admins_when_project_manager_not_assigned(): void
    {
        $this->project->update(['project_manager_id' => null]);

        Notification::fake();

        TimesheetNotifier::notifySubmitted($this->timesheet->fresh(['project.projectManager']));

        Notification::assertSentTo($this->admin, TimesheetSubmittedNotification::class);
        Notification::assertNotSentTo($this->pm, TimesheetSubmittedNotification::class);
    }

    public function test_pm_approval_notifies_assigned_project_director_only(): void
    {
        Notification::fake();

        TimesheetNotifier::notifyPendingDirector($this->timesheet, 'Looks good');

        Notification::assertSentTo($this->pd, TimesheetPendingDirectorNotification::class);
        Notification::assertNotSentTo($this->admin, TimesheetPendingDirectorNotification::class);
    }

    public function test_final_approval_notifies_employee(): void
    {
        Notification::fake();

        TimesheetNotifier::notifyApproved($this->timesheet, $this->pd, 'Approved');

        Notification::assertSentTo(
            $this->employee,
            TimesheetApprovedNotification::class,
            fn (TimesheetApprovedNotification $notification) => $notification->comment === 'Approved'
        );
    }

    public function test_rejection_notifies_employee(): void
    {
        Notification::fake();

        TimesheetNotifier::notifyRejected($this->timesheet, $this->pm, 'Please fix Friday hours');

        Notification::assertSentTo(
            $this->employee,
            TimesheetRejectedNotification::class,
            fn (TimesheetRejectedNotification $notification) => $notification->comment === 'Please fix Friday hours'
        );
    }

    public function test_notifications_are_skipped_when_disabled(): void
    {
        Setting::setValue('emailNotifications', false);

        Notification::fake();

        TimesheetNotifier::notifySubmitted($this->timesheet);
        TimesheetNotifier::notifyApproved($this->timesheet, $this->pm);

        Notification::assertNothingSent();
    }
}
