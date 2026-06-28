<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->load(['members', 'timesheets.user']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => ProjectResource::canEdit($this->record)),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Project details')
                    ->schema([
                        TextEntry::make('code')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('name'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => ucfirst($state))
                            ->color(fn (string $state) => match ($state) {
                                'active' => 'success',
                                'archived' => 'gray',
                                default => 'warning',
                            }),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Timeline & schedule')
                    ->schema([
                        TextEntry::make('start_date')
                            ->label('Start date')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('end_date')
                            ->label('End date')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('schedule_status')
                            ->label('Schedule status')
                            ->badge()
                            ->state(fn ($record) => $record->scheduleHealth()->label())
                            ->color(fn ($record) => $record->scheduleHealth()->color()),
                        TextEntry::make('duration_days')
                            ->label('Duration')
                            ->state(fn ($record) => ($days = $record->scheduleHealth()->durationDays()) !== null
                                ? "{$days} days"
                                : '—'),
                        TextEntry::make('days_remaining')
                            ->label('Days remaining')
                            ->state(fn ($record) => ($days = $record->scheduleHealth()->daysRemaining()) !== null
                                ? "{$days} days"
                                : '—'),
                        TextEntry::make('time_progress')
                            ->label('Timeline progress')
                            ->state(fn ($record) => ($progress = $record->scheduleHealth()->timeProgressPercent()) !== null
                                ? number_format($progress, 1) . '%'
                                : '—'),
                        TextEntry::make('hours_logged')
                            ->label('Hours logged')
                            ->state(fn ($record) => number_format($record->scheduleHealth()->hoursLogged(), 1) . 'h'),
                        TextEntry::make('expected_hours')
                            ->label('Expected hours to date')
                            ->state(fn ($record) => number_format($record->scheduleHealth()->expectedHoursToDate(), 1) . 'h'),
                        TextEntry::make('hours_progress')
                            ->label('Hours vs expected')
                            ->state(fn ($record) => ($progress = $record->scheduleHealth()->hoursProgressPercent()) !== null
                                ? number_format($progress, 1) . '%'
                                : '—'),
                    ])
                    ->columns(3),
                Section::make('Approvers')
                    ->schema([
                        TextEntry::make('projectManager.name')
                            ->label('Project Manager'),
                        TextEntry::make('projectDirector.name')
                            ->label('Project Director'),
                    ])
                    ->columns(2),
                Section::make('Team members')
                    ->schema([
                        TextEntry::make('members_list')
                            ->label('Assigned members')
                            ->state(fn ($record) => $record->members
                                ->sortBy('name')
                                ->map(fn ($member) => "{$member->name} — {$member->pivot->assigned_role}")
                                ->join("\n") ?: '—')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
                Section::make('Member contributions')
                    ->schema([
                        TextEntry::make('member_contributions')
                            ->label('Hours by member')
                            ->state(function ($record): string {
                                $rows = $record->memberContributions();

                                if ($rows === []) {
                                    return '—';
                                }

                                return collect($rows)
                                    ->map(fn (array $row): string => "{$row['name']} ({$row['role']}): {$row['hours']}h")
                                    ->join("\n");
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
                Section::make('Record')
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label('Created by')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}
