<?php

namespace App\Filament\Pages;

use App\Models\Timesheet;
use App\Models\TimesheetAttachment;
use App\Models\User;
use App\Support\TimesheetAccess;
use App\Support\WeeklyHoursAccess;
use App\Support\WeeklyHoursFormatter;
use App\Support\WeeklyHoursSheet;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class WeeklyHours extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Time Tracking';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Weekly hours';

    protected static ?string $title = 'Weekly hours';

    protected static ?string $slug = 'weekly-hours';

    protected string $view = 'filament.pages.weekly-hours';

    public ?int $selectedUserId = null;

    public string $weekStart = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /**
     * Pending file uploads keyed by row index. Livewire streams the selected
     * file here before {@see uploadAttachment()} persists it.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $rowUploads = [];

    public function mount(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $this->selectedUserId = auth()->id();
        $this->loadSheet();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printWeeklyHours')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => $this->getPrintUrl())
                ->openUrlInNewTab(),
            Action::make('downloadWeeklyHoursPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => $this->getPdfDownloadUrl())
                ->openUrlInNewTab(),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Weekly hours';
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['corp-weekly-hours-page'];
    }

    public function getWeekLabel(): string
    {
        $start = Carbon::parse($this->weekStart);

        return $start->format('F Y').' – Week '.$start->isoWeek();
    }

    /**
     * @return array<int, string>
     */
    public function getDayHeaders(): array
    {
        $start = Carbon::parse($this->weekStart)->startOfDay();

        return collect(range(0, 6))
            ->mapWithKeys(function (int $offset) use ($start): array {
                $date = $start->copy()->addDays($offset);

                return [$offset => $date->format('D j')];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getUserOptions(): array
    {
        $viewer = auth()->user();

        if (! $viewer || $viewer->isEmployee()) {
            return [];
        }

        $query = User::query()->orderBy('name');

        if ($viewer->isAdmin()) {
            return $query->pluck('name', 'id')->all();
        }

        return $query
            ->where(function ($userQuery) use ($viewer): void {
                $userQuery
                    ->whereHas('timesheets.project', fn ($projectQuery) => TimesheetAccess::scopeAssignedProjectsForUser($projectQuery, $viewer))
                    ->orWhere('id', $viewer->id);
            })
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getProjectOptions(): array
    {
        return $this->sheet()->projectOptions();
    }

    public function canEditSheet(): bool
    {
        $viewer = auth()->user();
        $target = $this->selectedUser();

        if (! $viewer || ! $target) {
            return false;
        }

        if ($viewer->isAdmin()) {
            return true;
        }

        return $viewer->id === $target->id;
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)
            ->subWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->format('Y-m-d');

        $this->loadSheet();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)
            ->addWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->format('Y-m-d');

        $this->loadSheet();
    }

    public function updatedSelectedUserId(): void
    {
        $this->authorizeSelectedUser();
        $this->loadSheet();
    }

    public function addRow(): void
    {
        if (! $this->canEditSheet()) {
            return;
        }

        $this->rows[] = [
            'id' => null,
            'project_id' => null,
            'project_name' => '',
            'activity' => '',
            'hours' => [0, 0, 0, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
            'editable' => true,
            'attachments' => [],
        ];
    }

    public function removeRow(int $index): void
    {
        if (! $this->canEditSheet()) {
            return;
        }

        if (! isset($this->rows[$index]) || ! ($this->rows[$index]['editable'] ?? false)) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    /**
     * Persist the file staged in {@see $rowUploads} for the given row. The row
     * must already be saved (have an id) and be editable by the current user.
     */
    public function uploadAttachment(int $rowIndex): void
    {
        if (! $this->canEditSheet()) {
            return;
        }

        $file = $this->rowUploads[$rowIndex] ?? null;
        $row = $this->rows[$rowIndex] ?? null;

        if (! $file || ! $row || ! ($row['editable'] ?? false)) {
            return;
        }

        $timesheetId = $row['id'] ?? null;

        if (! $timesheetId) {
            Notification::make()
                ->title('Save the row before attaching files.')
                ->warning()
                ->send();

            return;
        }

        $this->validate([
            "rowUploads.{$rowIndex}" => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,csv,txt'],
        ], attributes: ["rowUploads.{$rowIndex}" => 'attachment']);

        $this->authorizeSelectedUser();

        $timesheet = Timesheet::query()
            ->where('user_id', $this->selectedUserId)
            ->find($timesheetId);

        if (! $timesheet || ! TimesheetAccess::userCanEditTimesheet(auth()->user(), $timesheet)) {
            Notification::make()
                ->title('This row can no longer be edited.')
                ->danger()
                ->send();

            return;
        }

        $path = $file->store("timesheet-attachments/{$timesheet->id}", 'local');

        $timesheet->attachments()->create([
            'uploaded_by' => auth()->id(),
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        unset($this->rowUploads[$rowIndex]);
        $this->loadSheet();

        Notification::make()
            ->title('Attachment uploaded')
            ->success()
            ->send();
    }

    public function removeAttachment(int $attachmentId): void
    {
        if (! $this->canEditSheet()) {
            return;
        }

        $this->authorizeSelectedUser();

        $attachment = TimesheetAttachment::query()
            ->with('timesheet')
            ->whereHas('timesheet', fn ($query) => $query->where('user_id', $this->selectedUserId))
            ->find($attachmentId);

        if (! $attachment || ! $attachment->timesheet
            || ! TimesheetAccess::userCanEditTimesheet(auth()->user(), $attachment->timesheet)) {
            return;
        }

        $attachment->delete();
        $this->loadSheet();

        Notification::make()
            ->title('Attachment removed')
            ->success()
            ->send();
    }

    public function attachmentDownloadUrl(int $attachmentId): string
    {
        return route('timesheet-attachments.download', ['attachment' => $attachmentId]);
    }

    public function save(): void
    {
        if (! $this->canEditSheet()) {
            Notification::make()
                ->title('You cannot edit this weekly sheet.')
                ->danger()
                ->send();

            return;
        }

        $this->authorizeSelectedUser();

        try {
            $this->sheet()->saveRows($this->rows, auth()->user());
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Could not save weekly hours')
                ->body($this->formatValidationError($exception))
                ->danger()
                ->send();

            throw $exception;
        }

        $this->loadSheet();

        Notification::make()
            ->title('Weekly hours saved')
            ->body('Your timesheet rows for this week were updated.')
            ->success()
            ->send();
    }

    public function formatHours(float|int|string|null $hours): string
    {
        return WeeklyHoursFormatter::display($hours);
    }

    public function rowDuration(array $row): string
    {
        return WeeklyHoursFormatter::display(WeeklyHoursFormatter::rowTotal($row['hours'] ?? []));
    }

    public function rowOvertimeDuration(array $row): string
    {
        return WeeklyHoursFormatter::display(WeeklyHoursFormatter::rowTotal($row['overtime_hours'] ?? []));
    }

    /**
     * @return list<string>
     */
    public function columnTotals(): array
    {
        return array_map(
            fn (float $total): string => WeeklyHoursFormatter::display($total),
            WeeklyHoursFormatter::columnTotals($this->rows, 'hours'),
        );
    }

    /**
     * @return list<string>
     */
    public function columnOvertimeTotals(): array
    {
        return array_map(
            fn (float $total): string => WeeklyHoursFormatter::display($total),
            WeeklyHoursFormatter::columnTotals($this->rows, 'overtime_hours'),
        );
    }

    public function grandTotal(): string
    {
        $total = array_sum(WeeklyHoursFormatter::columnTotals($this->rows, 'hours'));

        return WeeklyHoursFormatter::display($total);
    }

    public function grandOvertimeTotal(): string
    {
        $total = array_sum(WeeklyHoursFormatter::columnTotals($this->rows, 'overtime_hours'));

        return WeeklyHoursFormatter::display($total);
    }

    public function getPdfDownloadUrl(): string
    {
        return route('pdf.weekly-hours', [
            'user' => $this->selectedUserId,
            'weekStart' => $this->weekStart,
        ]);
    }

    public function getPrintUrl(): string
    {
        return route('weekly-hours.print', [
            'user' => $this->selectedUserId,
            'weekStart' => $this->weekStart,
        ]);
    }

    protected function loadSheet(): void
    {
        $this->rows = $this->sheet()->loadRows();
    }

    protected function sheet(): WeeklyHoursSheet
    {
        return new WeeklyHoursSheet(
            $this->selectedUser(),
            Carbon::parse($this->weekStart)->startOfWeek(Carbon::MONDAY),
        );
    }

    public function selectedUser(): User
    {
        $user = User::query()->find($this->selectedUserId);

        if (! $user) {
            abort(404);
        }

        return $user;
    }

    protected function authorizeSelectedUser(): void
    {
        $viewer = auth()->user();
        $target = $this->selectedUser();

        if (! $viewer) {
            throw new AuthorizationException;
        }

        if ($viewer->isEmployee() && $viewer->id !== $target->id) {
            $this->selectedUserId = $viewer->id;
            $this->loadSheet();

            Notification::make()
                ->title('You can only view your own weekly hours.')
                ->danger()
                ->send();

            return;
        }

        if ($viewer->isAdmin()) {
            return;
        }

        if ($viewer->isEmployee()) {
            return;
        }

        if (! WeeklyHoursAccess::userCanView($viewer, $target)) {
            throw new AuthorizationException('You cannot view weekly hours for this user.');
        }
    }

    protected function formatValidationError(ValidationException $exception): string
    {
        return collect($exception->errors())
            ->flatMap(function (array $messages, string $key): array {
                if (preg_match('/^rows\.(\d+)\./', $key, $matches) !== 1) {
                    return $messages;
                }

                $rowNumber = ((int) $matches[1]) + 1;

                return array_map(
                    fn (string $message): string => "Row {$rowNumber}: {$message}",
                    $messages,
                );
            })
            ->first() ?? 'Please review the highlighted rows and try again.';
    }
}
