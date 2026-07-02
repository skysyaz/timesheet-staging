<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\TimesheetResource;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_timesheet_status_change_is_audited_without_sensitive_fields(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $project = Project::create(['code' => 'AUD-01', 'name' => 'Audit Project']);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'tasks' => ['Build', 'Review', 'Deploy', 'Test', 'Docs'],
            'status' => 'draft',
        ]);

        $this->actingAs($employee);
        $timesheet->update(['status' => 'pending_pm']);

        $activity = Activity::query()
            ->where('subject_type', Timesheet::class)
            ->where('subject_id', $timesheet->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('admin', $activity->log_name);

        $encoded = json_encode($activity->attribute_changes ?? $activity->properties);

        $this->assertStringNotContainsString('"hours"', (string) $encoded);
        $this->assertStringNotContainsString('"tasks"', (string) $encoded);
    }

    public function test_pm_approval_creates_manual_audit_entry(): void
    {
        Setting::create(['key' => 'requireProgramManagerApproval', 'value' => true]);

        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create();
        $project = Project::create([
            'code' => 'AUD-02',
            'name' => 'Approval Project',
            'project_manager_id' => $pm->id,
        ]);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_pm',
        ]);

        $this->actingAs($pm);
        TimesheetResource::handleApprove($timesheet, 'Looks good');

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Timesheet approved by PM, pending PD',
            'subject_type' => Timesheet::class,
            'subject_id' => $timesheet->id,
            'causer_id' => $pm->id,
        ]);
    }

    public function test_user_role_change_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($admin);
        $employee->update(['role' => 'project_manager']);

        $activity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $employee->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('project_manager', json_encode($activity->attribute_changes));
    }

    public function test_audit_logger_redacts_sensitive_properties(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        AuditLogger::log('Sensitive export attempted', null, [
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'password' => 'secret',
            'format' => 'csv',
        ]);

        $activity = Activity::query()->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame('[redacted]', $activity->properties->get('hours'));
        $this->assertSame('[redacted]', $activity->properties->get('password'));
        $this->assertSame('csv', $activity->properties->get('format'));
    }

    public function test_only_admins_can_access_audit_log_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($admin);
        $this->assertTrue(ActivityLogResource::canAccess());
        $this->assertTrue(ActivityLogResource::canViewAny());

        $this->actingAs($employee);
        $this->assertFalse(ActivityLogResource::canAccess());
        $this->assertFalse(ActivityLogResource::canViewAny());
    }

    public function test_audit_log_page_shows_empty_state_when_no_entries(): void
    {
        $admin = User::factory()->admin()->create();
        Activity::query()->delete();
        $this->actingAs($admin);

        Livewire::test(ListActivityLogs::class)
            ->assertOk()
            ->assertCountTableRecords(0);
    }
}
