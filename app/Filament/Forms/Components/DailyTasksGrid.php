<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class DailyTasksGrid extends Field
{
    protected string $view = 'filament.forms.components.daily-tasks-grid';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(['', '', '', '', '', '', '']);

        $this->dehydrateStateUsing(function (mixed $state): array {
            if (! is_array($state)) {
                return ['', '', '', '', '', '', ''];
            }

            return array_map(
                fn (mixed $value): string => trim((string) ($value ?? '')),
                array_replace(['', '', '', '', '', '', ''], array_values($state))
            );
        });
    }
}
