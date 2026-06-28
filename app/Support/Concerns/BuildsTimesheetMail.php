<?php

namespace App\Support\Concerns;

use App\Models\Timesheet;

trait BuildsTimesheetMail
{
    protected function timesheetSummary(Timesheet $timesheet): array
    {
        $timesheet->loadMissing(['user', 'project']);

        return [
            'Employee' => $timesheet->user?->name ?? '—',
            'Project' => $timesheet->project?->name ?? '—',
            'Week starting' => $timesheet->week_start?->format('d M Y') ?? '—',
            'Total hours' => number_format($timesheet->totalHours(), 1).'h',
        ];
    }

    protected function timesheetViewUrl(Timesheet $timesheet): string
    {
        return route('filament.admin.resources.timesheets.view', ['record' => $timesheet]);
    }
}
