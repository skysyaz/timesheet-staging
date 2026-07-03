<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Support\UserAccess;
use App\Support\UserNotifier;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('broadcastEmail')
                ->label('Broadcast email')
                ->icon('heroicon-o-envelope')
                ->modalHeading('Broadcast activation email')
                ->modalDescription('Sends your message plus a per-user set-password link to every user visible to you.')
                ->visible(fn () => UserAccess::canManageUsers(auth()->user()))
                ->form([
                    Forms\Components\TextInput::make('subject')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('body')
                        ->required()
                        ->rows(8),
                ])
                ->action(function (array $data): void {
                    $users = static::getResource()::getEloquentQuery()->get();

                    UserNotifier::sendBroadcast($users, $data['subject'], $data['body']);

                    Notification::make()
                        ->success()
                        ->title('Broadcast queued')
                        ->body("Activation emails queued for {$users->count()} users.")
                        ->send();
                }),
        ];
    }
}
