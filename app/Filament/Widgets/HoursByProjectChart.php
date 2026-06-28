<?php

namespace App\Filament\Widgets;

use App\Models\Timesheet;
use App\Support\TimesheetAccess;
use Filament\Widgets\ChartWidget;

class HoursByProjectChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    protected ?string $heading = 'Hours by Project';

    protected ?string $description = 'Distribution of logged hours across active projects';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $user = auth()->user();
        $query = Timesheet::with('project')
            ->select('project_id', 'hours');

        TimesheetAccess::scopeTimesheetsForUser($query, $user);

        $timesheets = $query->get();

        $projHours = [];
        foreach ($timesheets as $t) {
            $name = $t->project?->name ?? 'Unknown';
            $projHours[$name] = ($projHours[$name] ?? 0) + $t->totalHours();
        }

        arsort($projHours);

        $colors = [
            'rgba(37, 99, 235, 0.75)',
            'rgba(14, 165, 233, 0.75)',
            'rgba(16, 185, 129, 0.75)',
            'rgba(245, 158, 11, 0.75)',
            'rgba(99, 102, 241, 0.75)',
            'rgba(236, 72, 153, 0.75)',
            'rgba(100, 116, 139, 0.75)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Hours',
                    'data' => array_values($projHours),
                    'backgroundColor' => array_slice($colors, 0, count($projHours)),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_keys($projHours),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
