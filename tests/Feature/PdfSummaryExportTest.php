<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfSummaryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_pdf_honours_status_filter(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $project = Project::create(['code' => 'A', 'name' => 'Alpha']);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => $monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'approved',
        ]);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => $monday->copy()->subWeek(),
            'hours' => [4, 4, 4, 4, 4, 0, 0],
            'status' => 'draft',
        ]);

        $response = $this->actingAs($employee)->get('/pdf/summary?status=approved');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_summary_pdf_requires_authentication(): void
    {
        $this->get('/pdf/summary')->assertRedirect('/admin/login');
    }
}
