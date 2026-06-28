<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Header / Button Interactivity Safeguard
    |--------------------------------------------------------------------------
    |
    | When enabled, dispatches a client-side cleanup after saves and Livewire
    | commits to prevent stale modal overlays from blocking the profile menu.
    |
    */

    'consistent_buttons' => env('UI_CONSISTENT_BUTTONS', true),

];
