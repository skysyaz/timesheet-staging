<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\BroadcastEmail;
use App\Models\BroadcastTemplate;
use App\Support\UserAccess;
use App\Support\UserNotifier;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Set;
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
                ->modalDescription('Sends your message plus a per-user set-password link to every user visible to you. Pick a previous broadcast to reuse its message.')
                ->visible(fn () => UserAccess::canManageUsers(auth()->user()))
                ->form([
                    Forms\Components\Select::make('template')
                        ->label('Use a template')
                        ->options(fn () => BroadcastTemplate::query()
                            ->latest('id')
                            ->get()
                            ->mapWithKeys(fn (BroadcastTemplate $t) => [
                                $t->id => "{$t->name} ({$t->subject})",
                            ]))
                        ->placeholder('— none —')
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state): void {
                            $template = BroadcastTemplate::find((int) $state);
                            if ($template) {
                                $set('subject', $template->subject);
                                $set('body', $template->body);
                            }
                        }),
                    Forms\Components\Select::make('reuse')
                        ->label('Reuse a previous broadcast')
                        ->options(fn () => BroadcastEmail::query()
                            ->latest('id')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (BroadcastEmail $b) => [
                                $b->id => "{$b->subject} — {$b->created_at->format('Y-m-d')} ({$b->recipient_count} recipients)",
                            ]))
                        ->placeholder('— start fresh —')
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state): void {
                            $broadcast = BroadcastEmail::find((int) $state);
                            if ($broadcast) {
                                $set('subject', $broadcast->subject);
                                $set('body', $broadcast->body);
                            }
                        }),
                    Forms\Components\TextInput::make('subject')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('body')
                        ->required()
                        ->rows(8),
                ])
                ->action(function (array $data): void {
                    $users = static::getResource()::getEloquentQuery()->get();

                    UserNotifier::sendBroadcast($users, $data['subject'], $data['body'], auth()->user());

                    Notification::make()
                        ->success()
                        ->title('Broadcast queued')
                        ->body("Activation emails queued for {$users->count()} users.")
                        ->send();
                }),
        ];
    }
}
