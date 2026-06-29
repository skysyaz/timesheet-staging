<?php

namespace App\Filament\Resources\TimesheetResource\Concerns;

use App\Filament\Resources\TimesheetResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait CanSubmitTimesheet
{
    protected function makeSubmitTimesheetAction(bool $persistFormFirst = false): Actions\Action
    {
        return Actions\Action::make('submit')
            ->label('Submit')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->visible(fn (): bool => TimesheetResource::canUserSubmitTimesheet(auth()->user(), $this->record))
            ->requiresConfirmation()
            ->modalHeading('Submit timesheet')
            ->modalDescription(fn (): string => TimesheetResource::submitConfirmationMessage(auth()->user(), $this->record))
            ->action(function () use ($persistFormFirst): void {
                if ($persistFormFirst) {
                    $this->form->validate();
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $this->record->refresh();
                }

                try {
                    TimesheetResource::submitTimesheet($this->record);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->title('Cannot submit timesheet')
                        ->body(collect($exception->errors())->flatten()->first() ?? 'Please complete all required fields.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Timesheet submitted')
                    ->body(TimesheetResource::submitSuccessMessage(auth()->user(), $this->record))
                    ->success()
                    ->send();

                $this->record->refresh();

                if ($persistFormFirst) {
                    $this->redirect(TimesheetResource::getUrl('view', ['record' => $this->record]));
                }
            });
    }
}
