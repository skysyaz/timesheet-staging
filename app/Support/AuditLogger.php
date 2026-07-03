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
        ?string $logName = null,
    ): void {
        if (! config('activitylog.enabled')) {
            return;
        }

        // ponytail: log_name doubles as the actor's role so the Audit Log page's
        // "Log" badge reflects who acted (employee/project_admin/admin/...),
        // not a hardcoded "admin". Callers may still pass an explicit logName.
        $logName ??= Auth::user()?->role ?? 'system';

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
