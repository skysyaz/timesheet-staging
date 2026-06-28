<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestoresHeaderInteractivity;
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
            'requireDirectorApproval' => Setting::getValue('requireDirectorApproval', true),
            'emailNotifications' => Setting::getValue('emailNotifications', true),
        ]);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            Setting::setValue('standardWeeklyHours', (float) $data['standardWeeklyHours']);
            Setting::setValue('requireDirectorApproval', (bool) $data['requireDirectorApproval']);
            Setting::setValue('emailNotifications', (bool) $data['emailNotifications']);
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
            'requireDirectorApproval' => $data['requireDirectorApproval'],
            'emailNotifications' => $data['emailNotifications'],
        ]);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();

        $this->restoreHeaderInteractivity();
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
                    ->description('Configure how weekly overtime is calculated across dashboards and reports.')
                    ->schema([
                        Forms\Components\TextInput::make('standardWeeklyHours')
                            ->label('Standard weekly hours')
                            ->helperText('Weeks exceeding this total are counted as overtime. Common values: 40 (US), 37.5 (UK), 35 (France/Malaysia public sector).')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(168)
                            ->step(0.5)
                            ->suffix('hours'),
                    ]),
                Section::make('Approvals')
                    ->schema([
                        Forms\Components\Toggle::make('requireDirectorApproval')
                            ->label('Require Project Director approval')
                            ->helperText('When enabled, timesheets need both PM and PD sign-off before they are fully approved.'),
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
