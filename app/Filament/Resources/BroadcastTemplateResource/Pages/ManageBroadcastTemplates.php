<?php

namespace App\Filament\Resources\BroadcastTemplateResource\Pages;

use App\Filament\Resources\BroadcastTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBroadcastTemplates extends ManageRecords
{
    protected static string $resource = BroadcastTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['creator_id'] = auth()->id();

                    return $data;
                }),
        ];
    }
}
