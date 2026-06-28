<?php

namespace Tests\Feature;

use App\Filament\Pages\WeeklyHours;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WeeklyHoursPageTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private Project $projectA;

    private Project $projectB;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-24 09:00:00'));

        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $this->employee = User::factory()->create(['role' => 'employee', 'name' => 'Ainie Idris']);

        $this->projectA = Project::create([
            'code' => 'PRJ-A',
            'name' => 'Alpha Project',
            'status' => 'active',
        ]);
        $this->projectB = Project::create([
            'code' => 'PRJ-B',
            'name' => 'Beta Project',
            'status' => 'active',
        ]);

        $this->projectA->members()->attach($this->employee->id, ['assigned_role' => 'Developer']);
        $this->projectB->members()->attach($this->employee->id, ['assigned_role' => 'Support']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_can_access_weekly_hours_page(): void
    {
        $this->actingAs($this->employee)
            ->get('/admin/weekly-hours')
            ->assertOk();
    }

    public function test_activity_column_loads_from_tasks_not_project_role(): void
    {
        Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->projectA->id,
            'project_role' => 'QC Engineer',
            'week_start' => $this->monday->toDateString(),
            'hours' => [8, 8, 0, 0, 0, 0, 0],
            'tasks' => ['standby', 'db housekeeping', '', '', '', '', ''],
            'status' => 'draft',
        ]);

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->assertSet('rows.0.activity', 'standby, db housekeeping');
    }

    public function test_project_name_shows_even_when_employee_is_not_assigned_to_project(): void
    {
        $this->projectA->members()->detach($this->employee->id);
        $this->projectB->members()->detach($this->employee->id);

        Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->projectA->id,
            'project_role' => 'QC Engineer',
            'week_start' => $this->monday->toDateString(),
            'hours' => [2, 3, 0, 0, 0, 0, 0],
            'tasks' => ['standby', 'db housekeeping', '', '', '', '', ''],
            'status' => 'pending_pm',
        ]);

        Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->projectB->id,
            'project_role' => 'QC Engineer',
            'week_start' => $this->monday->toDateString(),
            'hours' => [0, 3, 0, 0, 0, 0, 0],
            'tasks' => ['', 'Standby monitor', '', '', '', '', ''],
            'status' => 'pending_pm',
        ]);

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->assertSet('rows.0.project_name', 'Alpha Project')
            ->assertSet('rows.1.project_name', 'Beta Project')
            ->assertCount('rows', 2);
    }

    public function test_employee_can_save_multiple_project_rows_for_one_week(): void
    {
        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rows', [
                [
                    'id' => null,
                    'project_id' => $this->projectA->id,
                    'activity' => 'Development',
                    'hours' => [8, 8, 8, 8, 8, 0, 0],
                    'status' => 'draft',
                    'editable' => true,
                ],
                [
                    'id' => null,
                    'project_id' => $this->projectB->id,
                    'activity' => 'Client support',
                    'hours' => [1, 1, 1, 1, 1, 0, 0],
                    'status' => 'draft',
                    'editable' => true,
                ],
            ])
            ->call('save')
            ->assertNotified('Weekly hours saved');

        $this->assertSame(2, Timesheet::query()->where('user_id', $this->employee->id)->count());

        $alpha = Timesheet::query()
            ->where('user_id', $this->employee->id)
            ->where('project_id', $this->projectA->id)
            ->first();

        $this->assertSame('Developer', $alpha->project_role);
        $this->assertSame(['Development', 'Development', 'Development', 'Development', 'Development', '', ''], $alpha->tasks);
    }

    public function test_duplicate_project_in_same_week_is_rejected(): void
    {
        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rows', [
                [
                    'id' => null,
                    'project_id' => $this->projectA->id,
                    'activity' => 'Dev',
                    'hours' => [4, 4, 4, 4, 4, 0, 0],
                    'status' => 'draft',
                    'editable' => true,
                ],
                [
                    'id' => null,
                    'project_id' => $this->projectA->id,
                    'activity' => 'QA',
                    'hours' => [4, 4, 4, 4, 4, 0, 0],
                    'status' => 'draft',
                    'editable' => true,
                ],
            ])
            ->call('save')
            ->assertNotified('Could not save weekly hours');

        $this->assertDatabaseCount('timesheets', 0);
    }

    public function test_week_navigation_loads_a_different_week(): void
    {
        Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->projectA->id,
            'project_role' => 'Developer',
            'week_start' => $this->monday->copy()->subWeek()->toDateString(),
            'hours' => [8, 0, 0, 0, 0, 0, 0],
            'tasks' => ['Planning', '', '', '', '', '', ''],
            'status' => 'draft',
        ]);

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->assertSet('weekStart', $this->monday->toDateString())
            ->call('previousWeek')
            ->assertSet('weekStart', $this->monday->copy()->subWeek()->toDateString())
            ->assertCount('rows', 1)
            ->assertSet('rows.0.project_id', $this->projectA->id)
            ->assertSet('rows.0.activity', 'Planning');
    }

    public function test_admin_can_save_weekly_hours_for_an_employee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(WeeklyHours::class)
            ->set('selectedUserId', $this->employee->id)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rows', [
                [
                    'id' => null,
                    'project_id' => $this->projectA->id,
                    'activity' => 'Site work',
                    'hours' => ['8:00', '8:00', '8:00', '8:00', '8:00', '0:00', '0:00'],
                    'status' => 'draft',
                    'editable' => true,
                ],
            ])
            ->call('save')
            ->assertNotified('Weekly hours saved');

        $timesheet = Timesheet::query()->first();

        $this->assertSame('Developer', $timesheet->project_role);
        $this->assertSame(['Site work', 'Site work', 'Site work', 'Site work', 'Site work', '', ''], $timesheet->tasks);
    }

    public function test_employee_cannot_switch_to_another_users_weekly_hours(): void
    {
        $otherEmployee = User::factory()->create(['role' => 'employee']);

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('selectedUserId', $otherEmployee->id)
            ->assertSet('selectedUserId', $this->employee->id);
    }

    public function test_employee_can_download_weekly_hours_pdf(): void
    {
        $this->seedWeeklyHours();

        $this->actingAs($this->employee)
            ->get(route('pdf.weekly-hours', [
                'user' => $this->employee->id,
                'weekStart' => $this->monday->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_employee_cannot_download_another_users_weekly_hours_pdf(): void
    {
        $otherEmployee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($this->employee)
            ->get(route('pdf.weekly-hours', [
                'user' => $otherEmployee->id,
                'weekStart' => $this->monday->toDateString(),
            ]))
            ->assertForbidden();
    }

    public function test_employee_can_open_weekly_hours_print_view(): void
    {
        $this->seedWeeklyHours();

        $this->actingAs($this->employee)
            ->get(route('weekly-hours.print', [
                'user' => $this->employee->id,
                'weekStart' => $this->monday->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Weekly Hours', false)
            ->assertSee('Alpha Project', false)
            ->assertSee('Development', false);
    }

    public function test_admin_can_download_weekly_hours_pdf_for_employee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seedWeeklyHours();

        $this->actingAs($admin)
            ->get(route('pdf.weekly-hours', [
                'user' => $this->employee->id,
                'weekStart' => $this->monday->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_weekly_hours_page_includes_valid_export_links(): void
    {
        $html = Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->html();

        $printUrl = route('weekly-hours.print', [
            'user' => $this->employee->id,
            'weekStart' => $this->monday->toDateString(),
        ]);
        $pdfUrl = route('pdf.weekly-hours', [
            'user' => $this->employee->id,
            'weekStart' => $this->monday->toDateString(),
        ]);

        $this->assertStringContainsString($printUrl, html_entity_decode($html));
        $this->assertStringContainsString($pdfUrl, html_entity_decode($html));
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    public function test_save_draft_without_activity_is_allowed(): void
    {
        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rows', [
                [
                    'id' => null,
                    'project_id' => $this->projectA->id,
                    'activity' => '',
                    'hours' => [8, 8, 8, 8, 8, 0, 0],
                    'status' => 'draft',
                    'editable' => true,
                ],
            ])
            ->call('save')
            ->assertNotified('Weekly hours saved');

        $this->assertDatabaseHas('timesheets', [
            'user_id' => $this->employee->id,
            'project_id' => $this->projectA->id,
            'project_role' => 'Developer',
            'status' => 'draft',
        ]);
    }

    public function test_save_without_row_id_updates_existing_draft_for_same_project(): void
    {
        $existing = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->projectA->id,
            'project_role' => 'Developer',
            'week_start' => $this->monday->toDateString(),
            'hours' => [4, 4, 4, 4, 4, 0, 0],
            'tasks' => ['Old activity', 'Old activity', 'Old activity', 'Old activity', 'Old activity', '', ''],
            'status' => 'draft',
        ]);

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rows', [
                [
                    'id' => null,
                    'project_id' => $this->projectA->id,
                    'activity' => 'Updated activity',
                    'hours' => [8, 8, 8, 8, 8, 0, 0],
                    'status' => 'draft',
                    'editable' => true,
                ],
            ])
            ->call('save')
            ->assertNotified('Weekly hours saved');

        $this->assertDatabaseCount('timesheets', 1);

        $existing->refresh();

        $this->assertSame('Developer', $existing->project_role);
        $this->assertSame(['Updated activity', 'Updated activity', 'Updated activity', 'Updated activity', 'Updated activity', '', ''], $existing->tasks);
        $this->assertSame([8, 8, 8, 8, 8, 0, 0], $existing->hours);
    }

    public function test_cannot_add_second_row_for_project_with_existing_submitted_timesheet(): void
    {
        Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->projectA->id,
            'project_role' => 'Developer',
            'week_start' => $this->monday->toDateString(),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'tasks' => ['Submitted work', 'Submitted work', 'Submitted work', 'Submitted work', 'Submitted work', '', ''],
            'status' => 'pending_pm',
        ]);

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rows', [
                [
                    'id' => null,
                    'project_id' => $this->projectA->id,
                    'activity' => 'Duplicate attempt',
                    'hours' => [2, 0, 0, 0, 0, 0, 0],
                    'status' => 'draft',
                    'editable' => true,
                ],
                [
                    'id' => null,
                    'project_id' => $this->projectB->id,
                    'activity' => 'Other project',
                    'hours' => [1, 0, 0, 0, 0, 0, 0],
                    'status' => 'draft',
                    'editable' => true,
                ],
            ])
            ->call('save')
            ->assertNotified('Could not save weekly hours');

        $this->assertDatabaseCount('timesheets', 1);
    }

    private function seedWeeklyHours(): void
    {
        Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->projectA->id,
            'project_role' => 'Developer',
            'week_start' => $this->monday->toDateString(),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'tasks' => ['Development', 'Development', 'Development', 'Development', 'Development', '', ''],
            'status' => 'draft',
        ]);

        Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->projectB->id,
            'project_role' => 'Support',
            'week_start' => $this->monday->toDateString(),
            'hours' => [1, 1, 1, 1, 1, 0, 0],
            'tasks' => ['Support', 'Support', 'Support', 'Support', 'Support', '', ''],
            'status' => 'draft',
        ]);
    }
}
