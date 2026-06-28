<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use App\Support\ProjectScheduleHealth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectScheduleHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);
        Carbon::setTestNow(Carbon::parse('2026-06-24 09:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_project_past_end_date_is_delayed(): void
    {
        $project = Project::create([
            'code' => 'DELAY',
            'name' => 'Delayed Project',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-20',
        ]);

        $this->assertSame(ProjectScheduleHealth::STATUS_DELAYED, $project->scheduleHealth()->status());
    }

    public function test_project_with_low_hours_mid_timeline_is_at_risk(): void
    {
        $employee = User::factory()->create();
        $project = Project::create([
            'code' => 'RISK',
            'name' => 'At Risk Project',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);
        $project->members()->attach($employee->id, ['assigned_role' => 'Developer']);

        $this->assertSame(ProjectScheduleHealth::STATUS_AT_RISK, $project->scheduleHealth()->status());
    }

    public function test_project_with_sufficient_hours_is_on_track(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-10 09:00:00'));

        $employee = User::factory()->create();
        $project = Project::create([
            'code' => 'GOOD',
            'name' => 'Healthy Project',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);
        $project->members()->attach($employee->id, ['assigned_role' => 'Developer']);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => '2026-06-02',
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'approved',
        ]);

        $this->assertSame(ProjectScheduleHealth::STATUS_ON_TRACK, $project->scheduleHealth()->status());
    }
}
