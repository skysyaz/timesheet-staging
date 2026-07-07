<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConfiguresTableToolbar;
use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use App\Support\TimesheetAccess;
use App\Support\UserAccess;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectResource extends Resource
{
    use ConfiguresTableToolbar;

    protected static ?string $model = Project::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';
    protected static string|\UnitEnum|null $navigationGroup = 'Management';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Project details')
                    ->description('Step 1 — Create the project record.')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('project_type_id')
                            ->label('Project type')
                            ->options(fn (): array => ProjectType::activeOptions())
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'archived' => 'Archived',
                            ])
                            ->default('active'),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make('Timeline')
                    ->description('Step 2 — Define the project start and end dates.')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->closeOnDateSelection()
                            ->live(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->closeOnDateSelection()
                            ->minDate(fn (Get $get) => $get('start_date'))
                            ->rule('after_or_equal:start_date'),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make('Approvers')
                    ->description('Assign who receives approval emails and can sign off timesheets for this project.')
                    ->schema([
                        Forms\Components\Select::make('project_manager_id')
                            ->label('Project Manager')
                            ->relationship(
                                'projectManager',
                                'name',
                                fn ($query) => $query->whereIn('role', ['project_manager', 'admin'])
                            )
                            ->default(fn () => auth()->user()?->isProjectManager() ? auth()->id() : null)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('program_manager_id')
                            ->label('Program Manager')
                            ->relationship(
                                'programManager',
                                'name',
                                fn ($query) => $query->whereIn('role', ['program_manager', 'admin'])
                            )
                            ->default(fn () => auth()->user()?->isProgramManager() ? auth()->id() : null)
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make('Team members')
                    ->description('Step 3 — Assign project members and their role on this project.')
                    ->schema([
                        Forms\Components\Repeater::make('member_assignments')
                            ->label('Project members')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Member')
                                    ->options(function (Get $get): array {
                                        $assignments = $get('member_assignments') ?? [];
                                        $currentUserId = filled($get('user_id')) ? (int) $get('user_id') : null;

                                        $assignedElsewhere = collect($assignments)
                                            ->pluck('user_id')
                                            ->filter()
                                            ->map(fn ($id) => (int) $id)
                                            ->reject(fn (int $id) => $currentUserId !== null && $id === $currentUserId)
                                            ->values()
                                            ->all();

                                        return User::query()
                                            ->when(
                                                auth()->user() && ! auth()->user()->isAdmin(),
                                                fn (Builder $query) => UserAccess::scopeAssignableProjectMembers($query, auth()->user()),
                                            )
                                            ->where('role', '!=', 'admin')
                                            ->when(
                                                $assignedElsewhere !== [],
                                                fn (Builder $query) => $query->whereNotIn('id', $assignedElsewhere),
                                            )
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn (User $user): array => [
                                                $user->id => "{$user->name} ({$user->email})",
                                            ])
                                            ->all();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->distinct(),
                                Forms\Components\TextInput::make('assigned_role')
                                    ->label('Role')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('e.g. Designer, Developer'),
                            ])
                            ->minItems(1)
                            ->required()
                            ->columns(2)
                            ->addActionLabel('Add member')
                            ->dehydrated(),
                    ]),
                \Filament\Schemas\Components\Section::make('Record')
                    ->schema([
                        Forms\Components\Placeholder::make('creator_display')
                            ->label('Created by')
                            ->content(fn (?Project $record): string => $record?->creator?->name ?? '—'),
                        Forms\Components\Placeholder::make('created_at_display')
                            ->label('Created at')
                            ->content(fn (?Project $record): string => $record?->created_at?->format('d/m/Y H:i') ?? '—'),
                    ])
                    ->columns(2)
                    ->visible(fn (?Project $record): bool => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureTableToolbar($table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('projectType.name')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->sortable(),
                Tables\Columns\TextColumn::make('projectManager.name')
                    ->label('PM')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('programManager.name')
                    ->label('PgM')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('schedule_status')
                    ->label('Schedule')
                    ->badge()
                    ->state(fn (Project $record): string => $record->scheduleHealth()->label())
                    ->color(fn (Project $record): string => $record->scheduleHealth()->color()),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'archived' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created by')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('project_type_id')
                    ->label('Project type')
                    ->relationship('projectType', 'name'),
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make()
                    ->visible(fn (Project $record) => static::canEdit($record)),
                static::configureProjectDeleteAction(DeleteAction::make())
                    ->visible(fn (Project $record): bool => ! $record->trashed() && (auth()->user()?->isAdmin() ?? false)),
                RestoreAction::make()
                    ->visible(fn (Project $record): bool => $record->trashed() && (auth()->user()?->isAdmin() ?? false)),
                ForceDeleteAction::make()
                    ->visible(fn (Project $record): bool => $record->trashed()
                        && (auth()->user()?->isAdmin() ?? false)
                        && $record->canBeForceDeleted())
                    ->modalDescription('This permanently removes the project. It can only be done when no timesheets exist.'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    static::configureProjectDeleteAction(DeleteBulkAction::make())
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                ]),
            ]));
    }

    public static function canEdit(Model $record): bool
    {
        return TimesheetAccess::userCanEditProject(
            auth()->user(),
            $record instanceof Project ? $record : null,
        );
    }

    public static function canView(Model $record): bool
    {
        return TimesheetAccess::userCanViewProject(
            auth()->user(),
            $record instanceof Project ? $record : null,
        );
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return (auth()->user()?->isAdmin() ?? false)
            && $record instanceof Project
            && $record->canBeForceDeleted();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [
            'admin',
            'project_admin',
            'project_manager',
            'program_manager',
        ], true);
    }

    public static function configureProjectDeleteAction(DeleteAction | DeleteBulkAction $action): DeleteAction | DeleteBulkAction
    {
        $action
            ->label('Move to trash')
            ->modalHeading('Move project to trash')
            ->successNotificationTitle('Project moved to trash');

        if ($action instanceof DeleteAction) {
            $action->modalDescription(fn (Project $record) => $record->trashDeletionMessage());
        } else {
            $action->modalDescription('Selected projects will be hidden from active lists. Timesheet records are kept and projects can be restored from the trash filter.');
        }

        return $action;
    }
}
