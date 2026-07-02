<?php

namespace App\Filament\Widgets;

use App\Models\Setting;
use App\Models\Timesheet;
use App\Support\TimesheetAccess;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TimesheetStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    /**
     * @return int | array<string, ?int> | null
     */
    protected function getColumns(): int | array | null
    {
        return 2;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $query = Timesheet::query();

        TimesheetAccess::scopeTimesheetsForUser($query, $user);

        $all = (clone $query)->get();
        $thisMonth = (clone $query)
            ->where('week_start', '>=', now()->startOfMonth())
            ->get();

        $total = $all->sum(fn ($t) => $t->totalHours());
        $monthHours = $thisMonth->sum(fn ($t) => $t->totalHours());
        $approved = $all->where('status', 'approved')->count();
        $pending = $all->whereIn('status', ['pending_pm', 'pending_program_manager'])->count();
        $std = Setting::standardWeeklyHours();
        $overtime = $all->sum(fn ($t) => $t->totalOvertimeHours());

        return [
            Stat::make('Total Hours', number_format($total, 1) . 'h')
                ->icon('heroicon-o-clock')
                ->description(number_format($monthHours, 1) . 'h this month')
                ->descriptionIcon('heroicon-o-clock')
                ->descriptionColor('primary')
                ->color('primary'),
            Stat::make('Approved', $approved)
                ->icon('heroicon-o-check-circle')
                ->description('Fully approved')
                ->descriptionIcon('heroicon-o-check-circle')
                ->descriptionColor('success')
                ->color('success'),
            Stat::make('Pending Review', $pending)
                ->icon('heroicon-o-arrow-path')
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->descriptionColor('warning')
                ->color('warning'),
            Stat::make('Overtime Hours', number_format($overtime, 1) . 'h')
                ->icon('heroicon-o-exclamation-triangle')
                ->description('Standard week is ' . $std . 'h')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->descriptionColor('danger')
                ->color('danger'),
        ];
    }
}
