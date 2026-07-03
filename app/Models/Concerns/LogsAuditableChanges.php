<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsAuditableChanges
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        // ponytail: log_name = the acting user's role (or 'system' for console/jobs),
        // so the Audit Log page's "Log" badge reflects who made the change, not a
        // hardcoded "admin". This runs at log time, so Auth::user() is available.
        return LogOptions::defaults()
            ->logOnly($this->auditableAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(Auth::user()?->role ?? 'system');
    }

    /**
     * @return list<string>
     */
    abstract protected function auditableAttributes(): array;
}
