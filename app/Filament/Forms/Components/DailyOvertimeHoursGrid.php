<?php

namespace App\Filament\Forms\Components;

use App\Rules\ValidDailyHours;
use Filament\Forms\Components\Field;

class DailyOvertimeHoursGrid extends Field
{
    protected string $view = 'filament.forms.components.daily-overtime-hours-grid';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([0, 0, 0, 0, 0, 0, 0]);

        $this->dehydrateStateUsing(function (mixed $state): array {
            if (! is_array($state)) {
                return [0, 0, 0, 0, 0, 0, 0];
            }

            return array_map(
                fn (mixed $value): float => (float) (filled($value) ? $value : 0),
                array_replace([0, 0, 0, 0, 0, 0, 0], array_values($state))
            );
        });

        $this->rule(new ValidDailyHours());
    }
}
