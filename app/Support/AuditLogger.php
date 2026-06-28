<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $description,
        ?Model $subject = null,
        array $properties = [],
        string $logName = 'admin',
    ): void {
        if (! config('activitylog.enabled')) {
            return;
        }

        $activity = activity($logName)
            ->causedBy(Auth::user())
            ->withProperties(static::redactProperties($properties));

        if ($subject !== null) {
            $activity->performedOn($subject);
        }

        $activity->log($description);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function redactProperties(array $properties): array
    {
        $redactKeys = [
            'password',
            'password_confirmation',
            'remember_token',
            'hours',
            'tasks',
            'notes',
            'app_authentication_secret',
            'app_authentication_recovery_codes',
        ];

        foreach ($redactKeys as $key) {
            if (array_key_exists($key, $properties)) {
                $properties[$key] = '[redacted]';
            }
        }

        return $properties;
    }
}
