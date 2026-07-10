<?php

namespace App\Filament\Actions;

use App\Models\User;
use App\Support\UserAccess;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;

class ResetUserPasswordAction
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function formSchema(): array
    {
        return [
            Forms\Components\TextInput::make('password')
                ->label('New password')
                ->password()
                ->revealable()
                ->required()
                ->rule(Password::defaults())
                ->confirmed(),
            Forms\Components\TextInput::make('password_confirmation')
                ->label('Confirm password')
                ->password()
                ->revealable()
                ->required(),
        ];
    }

    public static function make(string $name = 'resetPassword'): Action
    {
        return Action::make($name)
            ->label('Reset password')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->modalHeading('Reset user password')
            ->modalDescription('Set a temporary password for this user. They should change it after signing in.')
            ->form(static::formSchema())
            ->action(function (User $record, array $data): void {
                // Re-authorize server-side: ->visible() is UI-only and can be
                // bypassed via a crafted Livewire mount call.
                $actor = auth()->user();
                if (! $actor || ! UserAccess::canEditUser($actor, $record)) {
                    abort(403, 'You are not authorized to reset this user’s password.');
                }

                // The User model's `hashed` cast hashes the password on assign;
                // pass plaintext and let the cast be the single source of truth.
                $record->update([
                    'password' => $data['password'],
                ]);

                Notification::make()
                    ->success()
                    ->title('Password updated')
                    ->body("A new password has been set for {$record->name}.")
                    ->send();
            });
    }
}
