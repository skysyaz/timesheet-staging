<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Support\UserAccess;
use App\Support\UserNotifier;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        UserAccess::assertAssignableRole(auth()->user(), (string) ($data['role'] ?? ''));

        return $data;
    }

    protected function afterCreate(): void
    {
        // getRawState() holds the typed plaintext before the password field's
        // dehydrateStateUsing hashes it (getState()/mutateFormData run hashed).
        $plain = $this->form->getRawState()['password'] ?? null;

        if (filled($plain)) {
            UserNotifier::sendActivation($this->record, (string) $plain);
        }
    }
}
