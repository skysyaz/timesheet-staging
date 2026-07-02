<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Actions\ResetUserPasswordAction;
use App\Filament\Resources\UserResource;
use App\Support\UserAccess;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ResetUserPasswordAction::make()
                ->visible(fn () => UserAccess::canEditUser(auth()->user(), $this->record)),
            Actions\DeleteAction::make()
                ->visible(fn () => UserAccess::canDeleteUser(auth()->user(), $this->record)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        UserAccess::assertAssignableRole(auth()->user(), (string) ($data['role'] ?? ''));

        return $data;
    }
}
