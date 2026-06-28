<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    public function test_employee_cannot_download_another_employees_weekly_pdf(): void
    {
        $owner = User::factory()->create(['role' => 'employee']);
        $other = User::factory()->create(['role' => 'employee']);
        $timesheet = $this->createTimesheet($owner);

        $this->actingAs($other)
            ->get("/pdf/timesheet/{$timesheet->id}")
            ->assertForbidden();
    }

    public function test_employee_can_download_own_weekly_pdf(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $timesheet = $this->createTimesheet($employee);

        $this->actingAs($employee)
            ->get("/pdf/timesheet/{$timesheet->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_project_manager_can_download_assigned_project_weekly_pdf(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create();
        $project = Project::create([
            'code' => 'A',
            'name' => 'Alpha',
            'project_manager_id' => $pm->id,
        ]);
        $timesheet = $this->createTimesheet($employee, $project);

        $this->actingAs($pm)
            ->get("/pdf/timesheet/{$timesheet->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_project_manager_cannot_download_unassigned_project_weekly_pdf(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create();
        $otherPm = User::factory()->projectManager()->create();
        $project = Project::create([
            'code' => 'A',
            'name' => 'Alpha',
            'project_manager_id' => $otherPm->id,
        ]);
        $timesheet = $this->createTimesheet($employee, $project);

        $this->actingAs($pm)
            ->get("/pdf/timesheet/{$timesheet->id}")
            ->assertForbidden();
    }

    public function test_project_director_can_download_assigned_project_weekly_pdf(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pd = User::factory()->projectDirector()->create();
        $project = Project::create([
            'code' => 'A',
            'name' => 'Alpha',
            'project_director_id' => $pd->id,
        ]);
        $timesheet = $this->createTimesheet($employee, $project);

        $this->actingAs($pd)
            ->get("/pdf/timesheet/{$timesheet->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_project_director_cannot_download_unassigned_project_weekly_pdf(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pd = User::factory()->projectDirector()->create();
        $otherPd = User::factory()->projectDirector()->create();
        $project = Project::create([
            'code' => 'A',
            'name' => 'Alpha',
            'project_director_id' => $otherPd->id,
        ]);
        $timesheet = $this->createTimesheet($employee, $project);

        $this->actingAs($pd)
            ->get("/pdf/timesheet/{$timesheet->id}")
            ->assertForbidden();
    }

    public function test_admin_can_download_any_weekly_pdf(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $admin = User::factory()->admin()->create();
        $timesheet = $this->createTimesheet($employee);

        $this->actingAs($admin)
            ->get("/pdf/timesheet/{$timesheet->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_project_manager_summary_export_is_scoped_to_assigned_projects(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create();
        $assignedProject = Project::create([
            'code' => 'A',
            'name' => 'Assigned',
            'project_manager_id' => $pm->id,
        ]);
        $otherProject = Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
        ]);

        $this->createTimesheet($employee, $assignedProject, [8, 8, 8, 8, 8, 0, 0]);
        $this->createTimesheet($employee, $otherProject, [4, 4, 4, 4, 4, 0, 0]);

        $this->actingAs($pm)
            ->get('/pdf/summary')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_project_director_cannot_export_summary_for_unassigned_project_filter(): void
    {
        $pd = User::factory()->projectDirector()->create();
        $otherProject = Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_director_id' => User::factory()->projectDirector()->create()->id,
        ]);

        $this->actingAs($pd)
            ->get('/pdf/summary?projectId=' . $otherProject->id)
            ->assertForbidden();
    }

    public function test_admin_can_export_summary_for_all_projects(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $admin = User::factory()->admin()->create();
        $projectA = Project::create(['code' => 'A', 'name' => 'Alpha']);
        $projectB = Project::create(['code' => 'B', 'name' => 'Beta']);

        $this->createTimesheet($employee, $projectA);
        $this->createTimesheet($employee, $projectB);

        $this->actingAs($admin)
            ->get('/pdf/summary?projectId=' . $projectB->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_project_manager_cannot_export_summary_for_unassigned_project_filter(): void
    {
        $pm = User::factory()->projectManager()->create();
        $otherProject = Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($pm)
            ->get('/pdf/summary?projectId=' . $otherProject->id)
            ->assertForbidden();
    }

    public function test_summary_export_rejects_invalid_group_by(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/pdf/summary?groupBy=invalid')
            ->assertSessionHasErrors(['groupBy']);
    }

    public function test_summary_export_rejects_invalid_status(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/pdf/summary?status=not-a-status')
            ->assertSessionHasErrors(['status']);
    }

    public function test_summary_export_accepts_valid_filters(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $project = Project::create(['code' => 'A', 'name' => 'Alpha']);
        $this->createTimesheet($employee, $project);

        $this->actingAs($employee)
            ->get('/pdf/summary?groupBy=week&status=approved')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function createTimesheet(
        User $employee,
        ?Project $project = null,
        array $hours = [8, 8, 8, 8, 8, 0, 0],
    ): Timesheet {
        $project ??= Project::create(['code' => 'A', 'name' => 'Alpha']);

        return Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => $this->monday,
            'hours' => $hours,
            'status' => 'approved',
        ]);
    }
}
