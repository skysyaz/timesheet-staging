<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestoresHeaderInteractivity;
use App\Models\ProjectType;
use App\Models\Setting;
use App\Support\AuditLogger;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property-read Schema $form
 */
class Settings extends Page
{
    use CanUseDatabaseTransactions;
    use RestoresHeaderInteractivity;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->form->fill([
            'standardWeeklyHours' => Setting::standardWeeklyHours(),
            'overtimeDailyThreshold' => Setting::overtimeDailyThreshold(),
            'overtimeRate' => Setting::overtimeRate(),
            'requireProgramManagerApproval' => Setting::programManagerApprovalRequired(),
            'emailNotifications' => Setting::getValue('emailNotifications', true),
            'projectTypes' => ProjectType::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (ProjectType $type): array => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'is_active' => $type->is_active,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            Setting::setValue('standardWeeklyHours', (float) $data['standardWeeklyHours']);
            Setting::setValue('overtimeDailyThreshold', filled($data['overtimeDailyThreshold'] ?? null)
                ? (float) $data['overtimeDailyThreshold']
                : null);
            Setting::setValue('overtimeRate', (float) $data['overtimeRate']);
            Setting::setValue('requireProgramManagerApproval', (bool) $data['requireProgramManagerApproval']);
            Setting::setValue('emailNotifications', (bool) $data['emailNotifications']);

            $this->syncProjectTypes($data['projectTypes'] ?? []);
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        AuditLogger::log('Application settings updated', null, [
            'standardWeeklyHours' => $data['standardWeeklyHours'],
            'overtimeDailyThreshold' => $data['overtimeDailyThreshold'] ?? null,
            'overtimeRate' => $data['overtimeRate'],
            'requireProgramManagerApproval' => $data['requireProgramManagerApproval'],
            'emailNotifications' => $data['emailNotifications'],
        ]);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();

        $this->restoreHeaderInteractivity();
    }

    /**
     * @param  list<array{id?: int|null, name?: string|null, is_active?: bool}>  $rows
     */
    protected function syncProjectTypes(array $rows): void
    {
        $activeCount = collect($rows)->where('is_active', true)->count();

        if ($activeCount < 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.projectTypes' => 'At least one project type must remain active.',
            ]);
        }

        foreach (array_values($rows) as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "data.projectTypes.{$index}.name" => 'Project type name is required.',
                ]);
            }

            $attributes = [
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => (bool) ($row['is_active'] ?? true),
                'sort_order' => $index + 1,
            ];

            if (filled($row['id'] ?? null)) {
                ProjectType::query()->whereKey($row['id'])->update($attributes);
            } else {
                ProjectType::query()->create($attributes);
            }
        }
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Time & Overtime')
                    ->description('Configure weekly and daily thresholds and the overtime rate used in reports.')
                    ->schema([
                        Forms\Components\TextInput::make('standardWeeklyHours')
                            ->label('Standard weekly regular hours')
                            ->helperText('Regular hours per timesheet week cannot exceed this total.')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(168)
                            ->step(0.5)
                            ->suffix('hours'),
                        Forms\Components\TextInput::make('overtimeDailyThreshold')
                            ->label('Daily regular hours threshold')
                            ->helperText('Optional. Regular hours per day cannot exceed this value; use the overtime grid for additional hours.')
                            ->numeric()
                            ->minValue(0.5)
                            ->maxValue(24)
                            ->step(0.5)
                            ->suffix('hours'),
                        Forms\Components\TextInput::make('overtimeRate')
                            ->label('Overtime rate multiplier')
                            ->helperText('Applied to overtime hours in weighted totals and exports.')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10)
                            ->step(0.1),
                    ]),
                Section::make('Project types')
                    ->description('Manage project categories available when creating or editing projects.')
                    ->schema([
                        Forms\Components\Repeater::make('projectTypes')
                            ->label('Project types')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add project type')
                            ->reorderable()
                            ->collapsible(),
                    ]),
                Section::make('Approvals')
                    ->schema([
                        Forms\Components\Toggle::make('requireProgramManagerApproval')
                            ->label('Require Program Manager approval')
                            ->helperText('When enabled, timesheets need both PM and Program Manager sign-off before they are fully approved.'),
                    ]),
                Section::make('Notifications')
                    ->schema([
                        Forms\Components\Toggle::make('emailNotifications')
                            ->label('Email notifications')
                            ->helperText('Sends email on submit, approval, and rejection. Requires Resend domain verification and the queue worker on the server.'),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('settings-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment($this->getFormActionsAlignment())
                            ->key('settings-form-actions'),
                    ]),
            ]);
    }
}
