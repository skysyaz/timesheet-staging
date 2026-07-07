<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditableChanges;
use App\Support\ProjectScheduleHealth;
use App\Models\ProjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use LogsAuditableChanges;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (! $project->project_type_id) {
                $project->project_type_id = ProjectType::defaultId();
            }
        });
    }

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'project_manager_id',
        'program_manager_id',
        'project_type_id',
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

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function programManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'program_manager_id');
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

    public function isLedByProgramManager(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->canApproveAsProgramManager() && $this->program_manager_id === $user->id;
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

    public function timesheetCount(): int
    {
        return $this->timesheets()->count();
    }

    public function trashDeletionMessage(): string
    {
        $count = $this->timesheetCount();

        $base = 'The project will be hidden from active lists but can be restored by an admin.';

        if ($count > 0) {
            return "{$base} {$count} timesheet record(s) will be kept and are not deleted.";
        }

        return "{$base} This project has no timesheet records yet.";
    }

    public function canBeForceDeleted(): bool
    {
        return $this->timesheetCount() === 0;
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
            'program_manager_id',
            'project_type_id',
            'created_by',
        ];
    }
}
