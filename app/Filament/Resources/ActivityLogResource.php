<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $modelLabel = 'Audit entry';

    protected static ?string $pluralModelLabel = 'Audit log';

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->placeholder('System')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state, Activity $record): string => $state
                        ? Str::afterLast($state, '\\') . ' #' . ($record->subject_id ?? '—')
                        : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Details')
                    ->formatStateUsing(function ($state): string {
                        if (blank($state)) {
                            return '—';
                        }

                        $json = is_string($state) ? $state : json_encode($state);

                        return Str::limit((string) $json, 80);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->options(fn (): array => Activity::query()
                        ->select('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->filter()
                        ->all()),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Subject type')
                    ->options([
                        'App\Models\Timesheet' => 'Timesheet',
                        'App\Models\User' => 'User',
                        'App\Models\Project' => 'Project',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->schema([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->modalHeading('Audit entry')
                    ->modalContent(fn (Activity $record) => view('filament.resources.activity-log-entry', ['record' => $record])),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => static::exportCsv()),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No audit entries yet')
            ->emptyStateDescription('Workflow actions and admin changes appear here after they occur. Historical approval activity can be imported with `php artisan activitylog:backfill-approval-logs`.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function exportCsv(): StreamedResponse
    {
        $filename = 'audit-log-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['created_at', 'description', 'log_name', 'event', 'causer', 'subject_type', 'subject_id']);

            Activity::query()
                ->with('causer')
                ->orderByDesc('created_at')
                ->chunk(500, function ($activities) use ($handle): void {
                    foreach ($activities as $activity) {
                        fputcsv($handle, [
                            $activity->created_at?->toDateTimeString(),
                            $activity->description,
                            $activity->log_name,
                            $activity->event,
                            $activity->causer?->name,
                            $activity->subject_type,
                            $activity->subject_id,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
