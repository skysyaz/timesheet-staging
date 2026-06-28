<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use App\Support\TimesheetSummaryBuilder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimesheets extends ListRecords
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportSummary')
                ->label('Export Summary')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->url(fn (): string => TimesheetSummaryBuilder::fromTableFilters($this->tableFilters)->exportUrl())
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
        ];
    }
}
