<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use App\Filament\Resources\TimesheetResource\Concerns\CanSubmitTimesheet;
use App\Support\TimesheetAccess;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\Access\AuthorizationException;

class EditTimesheet extends EditRecord
{
    use CanSubmitTimesheet;

    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->makeSubmitTimesheetAction(persistFormFirst: true),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => TimesheetResource::canDelete($this->record)),
        ];
    }

    protected function beforeSave(): void
    {
        $this->record->refresh();

        if (! TimesheetAccess::userCanEditTimesheet(auth()->user(), $this->record)) {
            throw new AuthorizationException('This timesheet cannot be edited in its current status.');
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $hours = $this->record->hours ?? [0, 0, 0, 0, 0, 0, 0];
        $tasks = $this->record->tasks ?? ['', '', '', '', '', '', ''];
        $weekStart = $this->record->week_start->copy()->startOfDay();
        $today = now()->startOfDay();

        $data['hours'] = array_replace([0, 0, 0, 0, 0, 0, 0], $hours);
        $data['tasks'] = array_replace(['', '', '', '', '', '', ''], $tasks);

        if ($today->between($weekStart, $weekStart->copy()->addDays(6))) {
            $data['work_date'] = $today->format('Y-m-d');
        } else {
            $workDate = $weekStart;

            foreach ($hours as $index => $value) {
                if ((float) $value > 0) {
                    $workDate = $weekStart->copy()->addDays($index);
                    break;
                }
            }

            $data['work_date'] = $workDate->format('Y-m-d');
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['week_start'] ?? null)) {
            $data['week_start'] = Carbon::parse($data['week_start'])
                ->startOfWeek(Carbon::MONDAY)
                ->format('Y-m-d');
        }

        $data['tasks'] ??= ['', '', '', '', '', '', ''];
        $data['hours'] ??= [0, 0, 0, 0, 0, 0, 0];

        unset($data['work_date']);

        return $data;
    }
}
