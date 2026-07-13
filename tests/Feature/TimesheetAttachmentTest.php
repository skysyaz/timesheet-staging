<?php

namespace Tests\Feature;

use App\Filament\Pages\WeeklyHours;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\TimesheetAttachment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TimesheetAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private Project $project;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-24 09:00:00'));
        Storage::fake('local');

        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $this->employee = User::factory()->create(['role' => 'employee']);

        $this->project = Project::create([
            'code' => 'PRJ-A',
            'name' => 'Alpha Project',
            'status' => 'active',
        ]);
        $this->project->members()->attach($this->employee->id, ['assigned_role' => 'Developer']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function draftTimesheet(): Timesheet
    {
        return Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'project_role' => 'Developer',
            'week_start' => $this->monday->toDateString(),
            'hours' => [8, 0, 0, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'tasks' => ['Dev', '', '', '', '', '', ''],
            'status' => 'draft',
        ]);
    }

    public function test_employee_can_upload_attachment_to_a_saved_row(): void
    {
        $timesheet = $this->draftTimesheet();

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rowUploads.0', UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'))
            ->call('uploadAttachment', 0)
            ->assertHasNoErrors();

        $attachment = $timesheet->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame('receipt.pdf', $attachment->original_name);
        $this->assertSame($this->employee->id, $attachment->uploaded_by);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_upload_rejects_disallowed_file_type(): void
    {
        $this->draftTimesheet();

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rowUploads.0', UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'))
            ->call('uploadAttachment', 0)
            ->assertHasErrors('rowUploads.0');

        $this->assertSame(0, TimesheetAttachment::count());
    }

    public function test_attachment_cannot_be_added_to_locked_timesheet(): void
    {
        $timesheet = $this->draftTimesheet();
        $timesheet->update(['status' => 'approved']);

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->set('rowUploads.0', UploadedFile::fake()->create('receipt.pdf', 50, 'application/pdf'))
            ->call('uploadAttachment', 0);

        $this->assertSame(0, TimesheetAttachment::count());
    }

    public function test_employee_can_remove_own_attachment(): void
    {
        $timesheet = $this->draftTimesheet();
        $path = UploadedFile::fake()->create('note.txt', 5)->store('timesheet-attachments/'.$timesheet->id, 'local');

        $attachment = $timesheet->attachments()->create([
            'uploaded_by' => $this->employee->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'note.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
        ]);

        Livewire::actingAs($this->employee)
            ->test(WeeklyHours::class)
            ->set('weekStart', $this->monday->toDateString())
            ->call('removeAttachment', $attachment->id);

        $this->assertSame(0, TimesheetAttachment::count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_timesheet_removes_attachment_file(): void
    {
        $timesheet = $this->draftTimesheet();
        $path = UploadedFile::fake()->create('note.txt', 5)->store('timesheet-attachments/'.$timesheet->id, 'local');

        $timesheet->attachments()->create([
            'uploaded_by' => $this->employee->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'note.txt',
            'size' => 5,
        ]);

        $timesheet->delete();

        $this->assertSame(0, TimesheetAttachment::count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_owner_can_download_attachment_but_stranger_cannot(): void
    {
        $timesheet = $this->draftTimesheet();
        $path = UploadedFile::fake()->create('note.txt', 5)->store('timesheet-attachments/'.$timesheet->id, 'local');

        $attachment = $timesheet->attachments()->create([
            'uploaded_by' => $this->employee->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'note.txt',
            'size' => 5,
        ]);

        $stranger = User::factory()->create(['role' => 'employee']);

        $this->actingAs($stranger)
            ->get(route('timesheet-attachments.download', $attachment))
            ->assertForbidden();

        $this->actingAs($this->employee)
            ->get(route('timesheet-attachments.download', $attachment))
            ->assertOk();
    }
}
