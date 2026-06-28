<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

trait ConfiguresTableToolbar
{
    protected static function configureTableToolbar(Table $table): Table
    {
        return $table
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersApplyAction(fn (Action $action) => $action->close())
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->columnManagerApplyAction(
                fn (Action $action) => $action->alpineClickHandler('applyTableColumnManager(); close()'),
            );
    }
}
