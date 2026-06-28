<?php

namespace App\Filament\Concerns;

trait RestoresHeaderInteractivity
{
    protected function restoreHeaderInteractivity(): void
    {
        if (! config('ui.consistent_buttons')) {
            return;
        }

        $this->dispatch('restore-header-interactivity');
    }
}
