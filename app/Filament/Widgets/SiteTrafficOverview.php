<?php

namespace App\Filament\Widgets;

use App\Support\SiteTrafficRecorder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteTrafficOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return int | array<string, ?int> | null
     */
    protected function getColumns(): int | array | null
    {
        return 2;
    }

    protected function getStats(): array
    {
        /** @var SiteTrafficRecorder $recorder */
        $recorder = app(SiteTrafficRecorder::class);

        $todayViews = $recorder->todayPageViews();
        $weekViews = $recorder->totalPageViewsForDays(7);
        $weekSessions = $recorder->totalUniqueSessionsForDays(7);

        return [
            Stat::make('Site Traffic Today', number_format($todayViews))
                ->icon('heroicon-o-globe-alt')
                ->description('Page views across the admin app today')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->descriptionColor('info')
                ->color('info'),
            Stat::make('Traffic (7 days)', number_format($weekViews))
                ->icon('heroicon-o-chart-bar')
                ->description(number_format($weekSessions) . ' unique sessions this week')
                ->descriptionIcon('heroicon-o-users')
                ->descriptionColor('primary')
                ->color('primary'),
        ];
    }
}
