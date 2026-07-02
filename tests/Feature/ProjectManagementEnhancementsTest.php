<?php

namespace Tests\Feature;

use App\Filament\Pages\MyProjects;
use App\Filament\Pages\Reports;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectManagementEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_view_assigned_projects_page(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 09:00:00'));

        $employee = User::factory()->create(['role' => 'employee', 'name' => 'Ainie Idris']);
        $project = Project::create([
            'code' => 'WEB-01',
            'name' => 'Website Redesign',
            'description' => 'Corporate website refresh',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
        ]);
        $project->members()->attach($employee->id, ['assigned_role' => 'Designer']);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => '2026-06-22',
            'hours' => [8, 8, 8, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        Livewire::actingAs($employee)
            ->test(MyProjects::class)
            ->assertSee('Website Redesign')
            ->assertSee('Designer')
            ->assertSee('24.0h');

        Carbon::setTestNow();
    }

    public function test_reports_can_group_by_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employeeA = User::factory()->create(['name' => 'Ainie Idris']);
        $employeeB = User::factory()->create(['name' => 'John Smith']);
        $project = Project::create(['code' => 'RPT-01', 'name' => 'Report Project']);

        Timesheet::create([
            'user_id' => $employeeA->id,
            'project_id' => $project->id,
            'week_start' => '2026-06-22',
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);
        Timesheet::create([
            'user_id' => $employeeB->id,
            'project_id' => $project->id,
            'week_start' => '2026-06-22',
            'hours' => [4, 4, 4, 4, 4, 0, 0],
            'status' => 'approved',
        ]);

        Livewire::actingAs($admin)
            ->test(Reports::class)
            ->set('reportType', 'member')
            ->set('dateFrom', '2026-06-01')
            ->set('dateTo', '2026-06-30')
            ->assertSee('Ainie Idris')
            ->assertSee('John Smith');
    }

    public function test_admin_can_export_member_report_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['name' => 'Ainie Idris']);
        $project = Project::create(['code' => 'CSV-01', 'name' => 'CSV Project']);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => '2026-06-22',
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        Livewire::actingAs($admin)
            ->test(Reports::class)
            ->set('reportType', 'member')
            ->set('dateFrom', '2026-06-01')
            ->set('dateTo', '2026-06-30')
            ->call('exportCsv')
            ->assertFileDownloaded();
    }
}
