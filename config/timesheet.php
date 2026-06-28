<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Timesheet email notifications
    |--------------------------------------------------------------------------
    |
    | When queue is true, notifications are dispatched to the queue worker
    | (requires `php artisan queue:work`). When false, mail is sent during
    | the submit/approve HTTP request via sendNow().
    |
    */

    'notifications' => [
        'queue' => env('TIMESHEET_NOTIFICATIONS_QUEUE', true),
    ],

];
