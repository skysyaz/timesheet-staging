<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->load(['members', 'timesheets.user', 'projectType']);
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
                    ->icon('heroicon-o-folder-open')
                    ->schema([
                        TextEntry::make('code')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('name'),
                        TextEntry::make('projectType.name')
                            ->label('Project type'),
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
                    ->icon('heroicon-o-calendar-days')
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
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextEntry::make('projectManager.name')
                            ->label('Project Manager')
                            ->placeholder('Not assigned'),
                        TextEntry::make('programManager.name')
                            ->label('Program Manager')
                            ->placeholder('Not assigned'),
                    ])
                    ->columns(2),
                Flex::make([
                    Group::make([
                        Section::make('Team members')
                            ->icon('heroicon-o-users')
                            ->afterHeader([
                                TextEntry::make('team_members_count')
                                    ->hiddenLabel()
                                    ->state(fn ($record): string => $record->members()->count().' assigned')
                                    ->color('gray')
                                    ->size(TextSize::Small),
                            ])
                            ->schema([
                                ViewEntry::make('team_members')
                                    ->hiddenLabel()
                                    ->view('filament.infolists.project-team-members')
                                    ->columnSpanFull(),
                            ]),
                    ])->grow(),
                    Group::make([
                        Section::make('Member contributions')
                            ->icon('heroicon-o-chart-bar')
                            ->afterHeader([
                                TextEntry::make('member_contributions_count')
                                    ->hiddenLabel()
                                    ->state(fn ($record): string => count($record->memberContributions()).' contributors')
                                    ->color('gray')
                                    ->size(TextSize::Small),
                            ])
                            ->schema([
                                ViewEntry::make('member_contributions')
                                    ->hiddenLabel()
                                    ->view('filament.infolists.project-member-contributions')
                                    ->columnSpanFull(),
                            ]),
                    ])->grow(),
                ])
                    ->from('md')
                    ->columnSpanFull(),
                Section::make('Record')
                    ->icon('heroicon-o-clock')
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
