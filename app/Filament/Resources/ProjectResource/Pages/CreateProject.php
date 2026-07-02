<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Concerns\RestoresHeaderInteractivity;
use App\Filament\Concerns\SyncsProjectMembers;
use App\Filament\Resources\ProjectResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use RestoresHeaderInteractivity;
    use SyncsProjectMembers;

    protected static string $resource = ProjectResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        $memberCount = $this->record->members()->count();

        return Notification::make()
            ->success()
            ->title('Project created')
            ->body(sprintf(
                '%s saved with timeline %s to %s. %d team member(s) assigned.',
                $this->record->name,
                $this->record->start_date?->format('d/m/Y'),
                $this->record->end_date?->format('d/m/Y'),
                $memberCount,
            ));
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Create project')
            ->requiresConfirmation()
            ->modalHeading('Confirm project creation')
            ->modalDescription(function (): string {
                $data = $this->form->getState();
                $memberCount = count($data['member_assignments'] ?? []);

                return sprintf(
                    'Create "%s" (%s) from %s to %s with %d team member(s)?',
                    $data['name'] ?? '—',
                    $data['code'] ?? '—',
                    $data['start_date'] ?? '—',
                    $data['end_date'] ?? '—',
                    $memberCount,
                );
            })
            ->modalSubmitActionLabel('Yes, create project');
    }

    protected function beforeCreate(): void
    {
        $this->resolveMemberAssignments($this->form->getState()['member_assignments'] ?? []);
    }

    protected function afterCreate(): void
    {
        $this->record->members()->sync(
            $this->resolveMemberAssignments($this->form->getState()['member_assignments'] ?? []),
        );

        $this->restoreHeaderInteractivity();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['member_assignments']);

        $user = auth()->user();

        $data['created_by'] = $user?->id;

        if ($user?->isProjectManager() && empty($data['project_manager_id'])) {
            $data['project_manager_id'] = $user->id;
        }

        if ($user?->isProgramManager() && empty($data['program_manager_id'])) {
            $data['program_manager_id'] = $user->id;
        }

        return $data;
    }
}
