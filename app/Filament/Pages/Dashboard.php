<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = -2;

    protected static string|\UnitEnum|null $navigationGroup = 'Overview';

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['corp-dashboard'];
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}
