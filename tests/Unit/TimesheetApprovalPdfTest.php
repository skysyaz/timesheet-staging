<?php

namespace Tests\Unit;

use App\Models\ApprovalLog;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetApprovalPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_pdf_signature_fields_reflect_approvals(): void
    {
        $employee = User::factory()->create(['role' => 'employee', 'name' => 'Ali Staff']);
        $pm = User::factory()->projectManager()->create(['name' => 'Sara PM']);
        $pd = User::factory()->projectDirector()->create(['name' => 'Dan PD']);
        $project = Project::create(['code' => 'A', 'name' => 'Alpha']);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => $monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'approved',
        ]);

        ApprovalLog::create([
            'timesheet_id' => $timesheet->id,
            'user_id' => $employee->id,
            'action' => 'submitted',
            'created_at' => $monday->copy()->addDay(),
        ]);

        ApprovalLog::create([
            'timesheet_id' => $timesheet->id,
            'user_id' => $pm->id,
            'action' => 'approved_pm',
            'created_at' => $monday->copy()->addDays(2),
        ]);

        ApprovalLog::create([
            'timesheet_id' => $timesheet->id,
            'user_id' => $pd->id,
            'action' => 'approved_pd',
            'created_at' => $monday->copy()->addDays(3),
        ]);

        $timesheet->load(['approvalLogs.user']);

        $this->assertSame('Ali Staff', $timesheet->preparedByName());
        $this->assertSame('Sara PM', $timesheet->pmApproverName());
        $this->assertSame('Dan PD', $timesheet->pdApproverName());
        $this->assertSame($monday->copy()->addDays(2)->format('d/m/Y'), $timesheet->pmApproverDate());
        $this->assertSame($monday->copy()->addDays(3)->format('d/m/Y'), $timesheet->pdApproverDate());
    }

    public function test_weekly_pdf_endpoint_returns_pdf_with_approver_names(): void
    {
        $employee = User::factory()->create(['role' => 'employee', 'name' => 'Ali Staff']);
        $pm = User::factory()->projectManager()->create(['name' => 'Sara PM']);
        $project = Project::create(['code' => 'A', 'name' => 'Alpha']);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => $monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'pending_pd',
        ]);

        ApprovalLog::create([
            'timesheet_id' => $timesheet->id,
            'user_id' => $employee->id,
            'action' => 'submitted',
            'created_at' => $monday,
        ]);

        ApprovalLog::create([
            'timesheet_id' => $timesheet->id,
            'user_id' => $pm->id,
            'action' => 'approved_pm',
            'created_at' => $monday->copy()->addDay(),
        ]);

        $response = $this->actingAs($employee)->get("/pdf/timesheet/{$timesheet->id}");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_weekly_pdf_view_includes_project_role_and_tasks(): void
    {
        $employee = User::factory()->create(['role' => 'employee', 'name' => 'Ali Staff']);
        $project = Project::create(['code' => 'A', 'name' => 'Alpha']);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'project_role' => 'Site Engineer',
            'week_start' => $monday,
            'hours' => [8, 8, 0, 0, 0, 0, 0],
            'tasks' => ['Foundation work', 'Steel fixing', '', '', '', '', ''],
            'status' => 'draft',
        ]);

        $timesheet->load(['user', 'project']);

        $html = view('pdf.weekly', ['timesheet' => $timesheet])->render();

        $this->assertStringContainsString('Site Engineer', $html);
        $this->assertStringContainsString('Foundation work', $html);
        $this->assertStringContainsString('Steel fixing', $html);
    }
}
