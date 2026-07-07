<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Concerns\SyncsProjectMembers;
use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    use SyncsProjectMembers;

    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProjectResource::configureProjectDeleteAction(Actions\DeleteAction::make())
                ->visible(fn () => auth()->user()?->isAdmin() ?? false),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        $this->record->refresh();

        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Project saved';
    }

    protected function beforeSave(): void
    {
        $this->resolveMemberAssignments($this->form->getState()['member_assignments'] ?? []);
    }

    protected function afterSave(): void
    {
        $this->record->members()->sync(
            $this->resolveMemberAssignments($this->form->getState()['member_assignments'] ?? []),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load('members');

        $data['member_assignments'] = $this->record->members
            ->map(fn ($member): array => [
                'user_id' => $member->id,
                'assigned_role' => $member->pivot->assigned_role,
            ])
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['member_assignments']);

        return $data;
    }
}
