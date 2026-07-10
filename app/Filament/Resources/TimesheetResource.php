<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConfiguresTableToolbar;
use App\Filament\Forms\Components\DailyOvertimeHoursGrid;
use App\Filament\Forms\Components\DailyTasksGrid;
use App\Filament\Forms\Components\WeeklyTimesheetPlanner;
use App\Filament\Resources\TimesheetResource\Pages;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use App\Rules\ProjectMembershipForEmployee;
use App\Rules\ValidDailyHours;
use App\Rules\WeekStartsOnMonday;
use App\Support\AuditLogger;
use App\Support\OvertimeValidator;
use App\Support\ProjectDisplay;
use App\Support\TimesheetAccess;
use App\Support\TimesheetNotifier;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TimesheetResource extends Resource
{
    use ConfiguresTableToolbar;

    protected static ?string $model = Timesheet::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Time Tracking';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        return ($user && $user->isEmployee()) ? 'My Timesheets' : 'All Timesheets';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Project')
                    ->description('Choose where you worked and your role on the project.')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(fn (): array => TimesheetAccess::assignableUserOptionsForAdmin())
                            ->default(fn () => auth()->id())
                            ->required()
                            ->searchable()
                            ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                        Forms\Components\Select::make('project_id')
                            ->relationship(
                                'project',
                                'name',
                                function (Builder $query, $livewire): void {
                                    $query->where('status', 'active');

                                    if (! auth()->user()?->isEmployee()) {
                                        return;
                                    }

                                    $query->where(function (Builder $employeeProjects) use ($livewire): void {
                                        $employeeProjects->whereHas(
                                            'members',
                                            fn (Builder $members) => $members->whereKey(auth()->id()),
                                        );

                                        $record = method_exists($livewire, 'getRecord')
                                            ? $livewire->getRecord()
                                            : null;

                                        if ($record?->project_id) {
                                            $employeeProjects->orWhere('projects.id', $record->project_id);
                                        }
                                    });
                                },
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->rule(new ProjectMembershipForEmployee),
                        Forms\Components\TextInput::make('project_role')
                            ->label('Project role')
                            ->placeholder('e.g. Site Engineer, Developer')
                            ->maxLength(100)
                            ->required()
                            ->visible(fn (Get $get): bool => filled($get('project_id'))),
                    ])
                    ->columns(2),

                Section::make('Date')
                    ->description('Pick the day you want to log. The form opens on that day automatically.')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\DatePicker::make('work_date')
                            ->label('Work date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->default(Carbon::now()->format('Y-m-d'))
                            ->closeOnDateSelection()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($state, Set $set): void {
                                $date = filled($state)
                                    ? Carbon::parse($state)
                                    : Carbon::now();

                                if (blank($state)) {
                                    $set('work_date', $date->format('Y-m-d'));
                                }

                                $set('week_start', $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'));
                            })
                            ->afterStateUpdated(function (?string $state, Set $set, $livewire): void {
                                if (blank($state)) {
                                    return;
                                }

                                $date = Carbon::parse($state);
                                $set('week_start', $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'));

                                $livewire->dispatch(
                                    'timesheet-date-chosen',
                                    dayIndex: max(0, min(6, $date->dayOfWeekIso - 1)),
                                );
                            }),
                        Forms\Components\Hidden::make('week_start')
                            ->default(Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'))
                            ->dehydrated()
                            ->required(),
                    ])
                    ->visible(fn (Get $get): bool => filled($get('project_id')) && filled($get('project_role')))
                    ->columns(2),

                Section::make('Time entry')
                    ->description('Fill in hours and activity for the selected day, then move through the week.')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        WeeklyTimesheetPlanner::make('hours')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                        DailyOvertimeHoursGrid::make('overtime_hours')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                        DailyTasksGrid::make('tasks')
                            ->hiddenLabel()
                            ->view('filament.forms.components.empty-field')
                            ->dehydrated(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Additional notes')
                            ->placeholder('Optional notes for the whole week')
                            ->rows(2),
                    ])
                    ->visible(fn (Get $get): bool => filled($get('project_id')) && filled($get('project_role'))),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return static::configureTableToolbar($table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => $user && ! $user->isEmployee()),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(
                        fn (?string $state, Timesheet $record): string => ProjectDisplay::listLabel($record->project),
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas(
                            'project',
                            fn (Builder $projectQuery) => $projectQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%"),
                        );
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.code')
                    ->label('Project code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('week_start')
                    ->label('Week start')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('week_number')
                    ->label('Week')
                    ->getStateUsing(fn (Timesheet $record) => $record->week_start->isoWeek()),
                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total Hours')
                    ->getStateUsing(fn (Timesheet $record) => $record->totalHours().'h')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'pending_program_manager', 'pending_pm' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_pm' => 'Pending PM',
                        'pending_program_manager' => 'Pending Program Manager',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('project_id')
                    ->relationship(
                        'project',
                        'name',
                        fn (Builder $query) => TimesheetAccess::scopeProjectsForUser($query, auth()->user()),
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('approved_by_me')
                    ->label('Approved by me')
                    ->visible(fn () => $user?->isApprover() ?? false)
                    ->query(fn (Builder $query) => $query->whereHas(
                        'approvalLogs',
                        fn (Builder $logQuery) => $logQuery
                            ->where('user_id', auth()->id())
                            ->whereIn('action', ['approved_pm', 'approved_program_manager']),
                    )),
                Tables\Filters\SelectFilter::make('user_id')
                    ->options(fn (): array => TimesheetAccess::userFilterOptionsForViewer(auth()->user()))
                    ->searchable()
                    ->visible(fn () => $user && ! $user->isEmployee()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Timesheet $record) => auth()->user() && TimesheetAccess::userCanEditTimesheet(auth()->user(), $record)),
                Action::make('printPdf')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Timesheet $record) => route('pdf.weekly', $record))
                    ->openUrlInNewTab(),
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn (Timesheet $record) => static::canUserSubmitTimesheet($user, $record))
                    ->requiresConfirmation()
                    ->modalHeading('Submit timesheet')
                    ->modalDescription(fn (Timesheet $record): string => static::submitConfirmationMessage($user, $record))
                    ->action(function (Timesheet $record): void {
                        try {
                            static::submitTimesheet($record);
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
                            ->body(static::submitSuccessMessage(auth()->user(), $record->fresh(['project'])))
                            ->success()
                            ->send();
                    }),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Timesheet $record) => static::canApprove($record))
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Comment (optional)')
                            ->rows(2),
                    ])
                    ->action(function (Timesheet $record, array $data) {
                        static::handleApprove($record, $data['comment'] ?? '');
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Timesheet $record) => static::canReject($record))
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Reason for rejection')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (Timesheet $record, array $data) {
                        static::handleReject($record, $data['comment']);
                    }),
                Action::make('revertToDraft')
                    ->label('Revert to Draft')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Timesheet $record) => auth()->user() && TimesheetAccess::userCanRevertToDraft(auth()->user(), $record))
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
                    ->action(function (Timesheet $record, array $data) {
                        static::handleRevertToDraft($record, $data['reason']);
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('week_start', 'desc'));
    }

    public static function canApprove(Timesheet $record): bool
    {
        $user = auth()->user();

        return $user ? $record->canBeApprovedBy($user) : false;
    }

    public static function canReject(Timesheet $record): bool
    {
        $user = auth()->user();

        return $user ? $record->canBeRejectedBy($user) : false;
    }

    public static function canUserSubmitTimesheet(?User $user, Timesheet $record): bool
    {
        return $user instanceof User
            && $record->isSubmittable()
            && $record->user_id === $user->id
            && ! $record->isFutureWeek();
    }

    /**
     * @throws ValidationException
     */
    public static function validateForSubmission(Timesheet $record): void
    {
        $validator = Validator::make(
            [
                'project_id' => $record->project_id,
                'project_role' => $record->project_role,
                'week_start' => $record->week_start?->format('Y-m-d'),
                'hours' => $record->hours ?? [],
                'overtime_hours' => $record->overtime_hours ?? [],
            ],
            [
                'project_id' => ['required'],
                'project_role' => ['required', 'string', 'max:100'],
                'week_start' => ['required', new WeekStartsOnMonday],
                'hours' => ['required', new ValidDailyHours],
                'overtime_hours' => ['required', new ValidDailyHours],
            ],
        );

        $validator->after(function ($validator) use ($record): void {
            if ($record->totalHours() <= 0) {
                $validator->errors()->add('hours', 'Enter at least some hours before submitting.');
            }

            try {
                OvertimeValidator::validate(
                    $record->hours ?? [],
                    $record->overtime_hours ?? [],
                );
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }

    public static function submitConfirmationMessage(?User $user, Timesheet $record): string
    {
        // Ensure project is loaded before checking manager status
        if (! $record->relationLoaded('project')) {
            $record->load('project');
        }

        if ($user && $record->project?->isManagedBy($user) && $user->canApproveAsPm()) {
            return 'Submit this timesheet for program manager approval? You will not be able to edit it until it is rejected or reverted to draft.';
        }

        return 'Submit this timesheet for project manager approval? You will not be able to edit it until it is rejected or reverted to draft.';
    }

    public static function submitSuccessMessage(?User $user, Timesheet $record): string
    {
        if ($record->isPendingProgramManager()) {
            return 'Your timesheet has been sent to the program manager for approval.';
        }

        if ($record->isApproved()) {
            return 'Your timesheet has been approved.';
        }

        return 'Your timesheet has been sent for approval.';
    }

    /**
     * @throws ValidationException
     */
    public static function submitTimesheet(Timesheet $record): void
    {
        $user = auth()->user();

        if ($record->isFutureWeek()) {
            throw ValidationException::withMessages([
                'week_start' => 'This week has not started yet. Submit your timesheet once the week has begun.',
            ]);
        }

        if (! static::canUserSubmitTimesheet($user, $record)) {
            abort(403, 'You are not allowed to submit this timesheet.');
        }

        static::ensureProjectRole($record);
        $record->refresh();

        static::validateForSubmission($record);

        // Ensure project is loaded before accessing relationships
        if (! $record->relationLoaded('project')) {
            $record->load('project');
        }
        
        $project = $record->project;
        $requireProgramManager = Setting::programManagerApprovalRequired();

        $record->approvalLogs()->create([
            'user_id' => $user->id,
            'action' => 'submitted',
        ]);

        if ($project && $user->canApproveAsPm() && $project->isManagedBy($user)) {
            $record->approvalLogs()->create([
                'user_id' => $user->id,
                'action' => 'approved_pm',
                'comment' => 'Auto-approved as submitting project manager.',
            ]);

            if ($requireProgramManager) {
                $record->update(['status' => 'pending_program_manager']);

                AuditLogger::log('Timesheet submitted by PM, pending Program Manager', $record, [
                    'action' => 'approved_pm',
                    'status' => 'pending_program_manager',
                ]);

                TimesheetNotifier::notifyPendingProgramManager($record->fresh(['user', 'project']));

                return;
            }

            $record->update(['status' => 'approved']);

            TimesheetNotifier::notifyApproved($record->fresh(['user', 'project']), $user);

            AuditLogger::log('Timesheet submitted and approved by PM', $record, [
                'action' => 'approved_pm',
                'status' => 'approved',
            ]);

            return;
        }

        $record->update(['status' => 'pending_pm']);

        AuditLogger::log('Timesheet submitted for approval', $record, [
            'status' => 'pending_pm',
        ]);

        TimesheetNotifier::notifySubmitted($record->fresh(['user', 'project']));
    }

    public static function ensureProjectRole(Timesheet $record): void
    {
        if (filled($record->project_role)) {
            return;
        }

        $record->loadMissing('user');
        $user = $record->user;

        if (! $user || ! $record->project_id) {
            return;
        }

        $assignedRole = $user->projects()
            ->whereKey($record->project_id)
            ->value('project_user.assigned_role');

        if (filled($assignedRole)) {
            $record->update(['project_role' => (string) $assignedRole]);

            return;
        }

        $defaultRole = match ($user->role) {
            'project_manager' => 'Project Manager',
            'program_manager' => 'Program Manager',
            'admin' => 'Administrator',
            default => null,
        };

        if ($defaultRole !== null) {
            $record->update(['project_role' => $defaultRole]);
        }
    }

    public static function handleApprove(Timesheet $record, string $comment): void
    {
        $user = auth()->user();

        if ($record->isFutureWeek()) {
            throw ValidationException::withMessages([
                'week_start' => 'This week has not started yet; it cannot be approved until the week has begun.',
            ]);
        }

        if ($record->isPendingPm() && $record->canBeApprovedBy($user)) {
            $requireProgramManager = Setting::programManagerApprovalRequired();

            if ($requireProgramManager) {
                $record->update(['status' => 'pending_program_manager']);
                $record->approvalLogs()->create([
                    'user_id' => $user->id,
                    'action' => 'approved_pm',
                    'comment' => $comment,
                ]);

                TimesheetNotifier::notifyPendingProgramManager($record->fresh(['user', 'project']), $comment);

                AuditLogger::log('Timesheet approved by PM, pending Program Manager', $record, [
                    'action' => 'approved_pm',
                    'status' => 'pending_program_manager',
                ]);

                return;
            }

            $record->update(['status' => 'approved']);
            $record->approvalLogs()->create([
                'user_id' => $user->id,
                'action' => 'approved_pm',
                'comment' => $comment,
            ]);

            TimesheetNotifier::notifyApproved($record->fresh(['user', 'project']), $user, $comment);

            AuditLogger::log('Timesheet approved (PM, final)', $record, [
                'action' => 'approved_pm',
                'status' => 'approved',
            ]);

            return;
        }

        if ($record->isPendingProgramManager() && $record->canBeApprovedBy($user)) {
            $record->update(['status' => 'approved']);
            $record->approvalLogs()->create([
                'user_id' => $user->id,
                'action' => 'approved_program_manager',
                'comment' => $comment,
            ]);

            TimesheetNotifier::notifyApproved($record->fresh(['user', 'project']), $user, $comment);

            AuditLogger::log('Timesheet approved (Program Manager)', $record, [
                'action' => 'approved_program_manager',
                'status' => 'approved',
            ]);
        }
    }

    public static function handleReject(Timesheet $record, string $comment): void
    {
        $user = auth()->user();

        if (! $user || ! $record->canBeRejectedBy($user)) {
            abort(403, 'You are not allowed to reject this timesheet.');
        }

        $action = $record->isPendingPm() ? 'rejected_pm' : 'rejected_program_manager';

        $record->update(['status' => 'rejected']);
        $record->approvalLogs()->create([
            'user_id' => $user->id,
            'action' => $action,
            'comment' => $comment,
        ]);

        TimesheetNotifier::notifyRejected($record->fresh(['user', 'project']), $user, $comment);

        AuditLogger::log('Timesheet rejected', $record, [
            'action' => $action,
            'status' => 'rejected',
        ]);
    }

    public static function handleRevertToDraft(Timesheet $record, string $reason): void
    {
        $user = auth()->user();

        if (! $user || ! TimesheetAccess::userCanRevertToDraft($user, $record)) {
            abort(403, 'You are not allowed to revert this timesheet.');
        }

        $record->update(['status' => 'draft']);
        $record->approvalLogs()->create([
            'user_id' => $user->id,
            'action' => 'reverted_to_draft',
            'comment' => $reason,
        ]);

        AuditLogger::log('Timesheet reverted to draft', $record, [
            'action' => 'reverted_to_draft',
            'status' => 'draft',
        ]);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $record instanceof Timesheet
            && TimesheetAccess::userCanEditTimesheet($user, $record);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $record instanceof Timesheet
            && TimesheetAccess::userCanViewTimesheet($user, $record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record) && $record instanceof Timesheet && $record->isDraft();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['project', 'user']);
        $user = auth()->user();

        if ($user) {
            TimesheetAccess::scopeTimesheetsForUser($query, $user);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimesheets::route('/'),
            'create' => Pages\CreateTimesheet::route('/create'),
            'edit' => Pages\EditTimesheet::route('/{record}/edit'),
            'view' => Pages\ViewTimesheet::route('/{record}'),
        ];
    }
}
