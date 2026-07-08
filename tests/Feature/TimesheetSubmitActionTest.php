<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource;
use App\Filament\Resources\TimesheetResource\Pages\EditTimesheet;
use App\Filament\Resources\TimesheetResource\Pages\ViewTimesheet;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TimesheetSubmitActionTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private Project $project;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create(['role' => 'employee']);
        $this->project = Project::create(['code' => 'SUB-01', 'name' => 'Submit Project']);
        $this->project->members()->attach($this->employee->id);
        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    private function draftTimesheet(array $overrides = []): Timesheet
    {
        return Timesheet::create(array_merge([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'project_role' => 'Site Engineer',
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ], $overrides));
    }

    public function test_submit_validation_requires_hours(): void
    {
        $timesheet = $this->draftTimesheet(['hours' => [0, 0, 0, 0, 0, 0, 0]]);

        $this->expectException(ValidationException::class);

        TimesheetResource::validateForSubmission($timesheet);
    }

    public function test_submit_updates_status_and_creates_approval_log(): void
    {
        $timesheet = $this->draftTimesheet();
        $this->actingAs($this->employee);

        TimesheetResource::submitTimesheet($timesheet);

        $timesheet->refresh();

        $this->assertSame('pending_pm', $timesheet->status);
        $this->assertDatabaseHas('approval_logs', [
            'timesheet_id' => $timesheet->id,
            'user_id' => $this->employee->id,
            'action' => 'submitted',
        ]);
    }

    public function test_employee_can_submit_from_view_page(): void
    {
        $timesheet = $this->draftTimesheet();
        $this->actingAs($this->employee);

        Livewire::test(ViewTimesheet::class, ['record' => $timesheet->getRouteKey()])
            ->assertActionVisible('submit')
            ->callAction('submit')
            ->assertNotified('Timesheet submitted');

        $this->assertSame('pending_pm', $timesheet->fresh()->status);
    }

    public function test_employee_can_submit_from_edit_page(): void
    {
        $timesheet = $this->draftTimesheet();
        $this->actingAs($this->employee);

        Livewire::test(EditTimesheet::class, ['record' => $timesheet->getRouteKey()])
            ->assertActionVisible('submit')
            ->callAction('submit')
            ->assertNotified('Timesheet submitted')
            ->assertRedirect(TimesheetResource::getUrl('view', ['record' => $timesheet]));

        $this->assertSame('pending_pm', $timesheet->fresh()->status);
    }

    public function test_submit_action_hidden_for_pending_timesheet(): void
    {
        $timesheet = $this->draftTimesheet(['status' => 'pending_pm']);
        $this->actingAs($this->employee);

        Livewire::test(ViewTimesheet::class, ['record' => $timesheet->getRouteKey()])
            ->assertActionHidden('submit');
    }

    public function test_other_employee_cannot_submit_timesheet(): void
    {
        $timesheet = $this->draftTimesheet();
        $otherEmployee = User::factory()->create(['role' => 'employee']);
        $this->actingAs($otherEmployee);

        $this->assertFalse(TimesheetResource::canUserSubmitTimesheet($otherEmployee, $timesheet));
    }

    public function test_future_week_timesheet_is_not_submittable(): void
    {
        $nextMonday = $this->monday->copy()->addWeek();
        $timesheet = $this->draftTimesheet(['week_start' => $nextMonday]);

        $this->assertTrue($timesheet->isFutureWeek());
        $this->assertFalse(TimesheetResource::canUserSubmitTimesheet($this->employee, $timesheet));
    }

    public function test_submit_timesheet_blocks_future_week_with_friendly_error(): void
    {
        $nextMonday = $this->monday->copy()->addWeek();
        $timesheet = $this->draftTimesheet(['week_start' => $nextMonday]);
        $this->actingAs($this->employee);

        try {
            TimesheetResource::submitTimesheet($timesheet);
            $this->fail('Expected ValidationException for a future-week submission.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'has not started yet',
                collect($exception->validator->errors()->get('week_start'))->first(),
            );
        }

        $this->assertSame('draft', $timesheet->fresh()->status);
        $this->assertDatabaseMissing('approval_logs', [
            'timesheet_id' => $timesheet->id,
            'action' => 'submitted',
        ]);
    }

    public function test_submit_action_hidden_for_future_week_timesheet(): void
    {
        $nextMonday = $this->monday->copy()->addWeek();
        $timesheet = $this->draftTimesheet(['week_start' => $nextMonday]);
        $this->actingAs($this->employee);

        Livewire::test(ViewTimesheet::class, ['record' => $timesheet->getRouteKey()])
            ->assertActionHidden('submit');
    }
}
