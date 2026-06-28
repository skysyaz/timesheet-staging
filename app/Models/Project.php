<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditableChanges;
use App\Support\ProjectScheduleHealth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use LogsAuditableChanges;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'project_manager_id',
        'project_director_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function projectDirector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_director_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('assigned_role')
            ->withTimestamps();
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }

    public function isManagedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->canApproveAsPm() && $this->project_manager_id === $user->id;
    }

    public function isDirectedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->canApproveAsPd() && $this->project_director_id === $user->id;
    }

    public function totalHours(): float
    {
        return (float) $this->timesheets()
            ->get()
            ->sum(fn ($timesheet): float => $timesheet->totalHours());
    }

    public function scheduleHealth(): ProjectScheduleHealth
    {
        return new ProjectScheduleHealth($this);
    }

    /**
     * @return list<array{name: string, role: string, hours: float}>
     */
    public function memberContributions(): array
    {
        $this->loadMissing('members');

        return $this->timesheets()
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($timesheets, int $userId): array {
                $member = $this->members->firstWhere('id', $userId);

                return [
                    'name' => $timesheets->first()?->user?->name ?? 'Unknown',
                    'role' => $member?->pivot?->assigned_role ?? '—',
                    'hours' => round($timesheets->sum(fn ($timesheet): float => $timesheet->totalHours()), 1),
                ];
            })
            ->sortByDesc('hours')
            ->values()
            ->all();
    }

    public function employeeCount(): int
    {
        return $this->timesheets()->distinct('user_id')->count('user_id');
    }

    /**
     * @return list<string>
     */
    protected function auditableAttributes(): array
    {
        return [
            'code',
            'name',
            'description',
            'status',
            'start_date',
            'end_date',
            'project_manager_id',
            'project_director_id',
            'created_by',
        ];
    }
}
