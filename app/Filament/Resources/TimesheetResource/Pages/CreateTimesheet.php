<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! isset($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }

        if (blank($data['week_start'] ?? null)) {
            $data['week_start'] = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        } else {
            $data['week_start'] = Carbon::parse($data['week_start'])
                ->startOfWeek(Carbon::MONDAY)
                ->format('Y-m-d');
        }

        $data['status'] ??= 'draft';
        $data['tasks'] ??= ['', '', '', '', '', '', ''];
        $data['hours'] ??= [0, 0, 0, 0, 0, 0, 0];

        unset($data['work_date']);

        return $data;
    }
}
