<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use App\Filament\Resources\TimesheetResource\Concerns\CanSubmitTimesheet;
use App\Support\ProjectDisplay;
use App\Support\TimesheetAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ViewTimesheet extends ViewRecord
{
    use CanSubmitTimesheet;

    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->record;
        $user = auth()->user();

        return [
            $this->makeSubmitTimesheetAction(),
            Actions\EditAction::make()
                ->visible(fn () => $user && TimesheetAccess::userCanEditTimesheet($user, $record)),
            Actions\Action::make('printPdf')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('pdf.weekly', $record))
                ->openUrlInNewTab(),
            Actions\Action::make('revertToDraft')
                ->label('Revert to Draft')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => $user && TimesheetAccess::userCanRevertToDraft($user, $record))
                ->requiresConfirmation()
                ->modalHeading('Revert approved timesheet to draft')
                ->modalDescription('This will unlock the timesheet so the employee can edit and resubmit it. The action is recorded in the approval history.')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason for revert')
                        ->required()
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    TimesheetResource::handleRevertToDraft($this->record, $data['reason']);
                    $this->record->refresh();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Timesheet Details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Employee')
                            ->visible(fn () => ! auth()->user()->isEmployee()),
                        TextEntry::make('project.name')
                            ->label('Project')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(
                                fn (?string $state, Model $record): string => ProjectDisplay::listLabel($record->project),
                            ),
                        TextEntry::make('project_role')
                            ->label('Project Role')
                            ->placeholder('—'),
                        TextEntry::make('week_start')
                            ->label('Week Starting')
                            ->date('d/m/Y'),
                        TextEntry::make('week_number')
                            ->label('Week Number')
                            ->getStateUsing(fn (Model $record) => 'Week ' . $record->week_start->isoWeek() . ', ' . $record->week_start->year),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'approved' => 'success',
                                'pending_pd', 'pending_pm' => 'warning',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),
                    ])->columns(3),

                Section::make('Weekly Breakdown')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        ViewEntry::make('hours_grid')
                            ->view('filament.infolists.daily-hours-grid'),
                        ViewEntry::make('tasks_grid')
                            ->view('filament.infolists.daily-tasks-grid')
                            ->visible(fn (Model $record) => collect($record->tasks ?? [])->contains(fn ($task) => filled($task))),
                    ]),

                Section::make('Approval History')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('approvalLogs')
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('By'),
                                TextEntry::make('action')
                                    ->label('Action')
                                    ->badge()
                                    ->color(fn (string $state) => match ($state) {
                                        'submitted' => 'info',
                                        'approved_pm', 'approved_pd' => 'success',
                                        'rejected_pm', 'rejected_pd' => 'danger',
                                        'reverted_to_draft' => 'warning',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),
                                TextEntry::make('comment')
                                    ->visible(fn ($state) => filled($state)),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime('M j, Y g:i A'),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Notes')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        TextEntry::make('notes')
                            ->hiddenLabel(),
                    ])
                    ->visible(fn (Model $record) => filled($record->notes)),
            ]);
    }
}
