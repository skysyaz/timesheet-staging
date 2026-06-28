<?php

namespace App\Filament\Widgets;

use App\Models\Timesheet;
use App\Support\TimesheetAccess;
use Filament\Widgets\ChartWidget;

class HoursByDayChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '240px';

    protected ?string $heading = 'Hours by Day of Week';

    protected ?string $description = 'Average daily hours logged across all timesheets';

    protected function getData(): array
    {
        $user = auth()->user();
        $query = Timesheet::query();

        TimesheetAccess::scopeTimesheetsForUser($query, $user);

        $timesheets = $query->get(['hours']);
        $daily = [0, 0, 0, 0, 0, 0, 0];

        foreach ($timesheets as $t) {
            $hours = $t->hours ?? [0, 0, 0, 0, 0, 0, 0];
            foreach ($hours as $i => $h) {
                $daily[$i] += (float) $h;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Hours',
                    'data' => $daily,
                    'backgroundColor' => [
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(148, 163, 184, 0.5)',
                        'rgba(148, 163, 184, 0.5)',
                    ],
                    'borderColor' => 'rgba(37, 99, 235, 1)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
