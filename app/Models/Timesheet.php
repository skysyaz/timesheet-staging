<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditableChanges;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    use LogsAuditableChanges;

    protected $fillable = [
        'user_id',
        'project_id',
        'project_role',
        'week_start',
        'hours',
        'tasks',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'hours' => 'array',
            'tasks' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function approvalLogs()
    {
        return $this->hasMany(ApprovalLog::class);
    }

    public function latestApprovalLog(string $action): ?ApprovalLog
    {
        if ($this->relationLoaded('approvalLogs')) {
            return $this->approvalLogs
                ->where('action', $action)
                ->sortByDesc('created_at')
                ->first();
        }

        return $this->approvalLogs()
            ->where('action', $action)
            ->with('user')
            ->latest()
            ->first();
    }

    public function preparedByName(): string
    {
        return $this->latestApprovalLog('submitted')?->user?->name
            ?? $this->user?->name
            ?? '';
    }

    public function preparedByDate(): string
    {
        $date = $this->latestApprovalLog('submitted')?->created_at
            ?? $this->week_start;

        return $date?->format('d/m/Y') ?? '';
    }

    public function pmApproverName(): string
    {
        return $this->latestApprovalLog('approved_pm')?->user?->name ?? '';
    }

    public function pmApproverDate(): string
    {
        return $this->latestApprovalLog('approved_pm')?->created_at?->format('d/m/Y') ?? '';
    }

    public function pdApproverName(): string
    {
        return $this->latestApprovalLog('approved_pd')?->user?->name ?? '';
    }

    public function pdApproverDate(): string
    {
        return $this->latestApprovalLog('approved_pd')?->created_at?->format('d/m/Y') ?? '';
    }

    public function totalHours(): float
    {
        return array_sum($this->hours ?? [0, 0, 0, 0, 0, 0, 0]);
    }

    public function taskForDay(int $index): string
    {
        $tasks = $this->tasks ?? [];
        $task = trim((string) ($tasks[$index] ?? ''));

        if ($task !== '') {
            return $task;
        }

        $hours = (float) ($this->hours[$index] ?? 0);

        if ($hours > 0 && filled($this->notes)) {
            return (string) $this->notes;
        }

        return '';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingPm(): bool
    {
        return $this->status === 'pending_pm';
    }

    public function isPendingPd(): bool
    {
        return $this->status === 'pending_pd';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function isSubmittable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canBeApprovedBy(User $user): bool
    {
        $this->loadMissing('project');
        $project = $this->project;

        if ($this->isPendingPm()) {
            return $project?->isManagedBy($user) ?? false;
        }

        if ($this->isPendingPd()) {
            return $project?->isDirectedBy($user) ?? false;
        }

        return false;
    }

    public function canBeRejectedBy(User $user): bool
    {
        return $this->canBeApprovedBy($user);
    }

    /**
     * @return list<string>
     */
    protected function auditableAttributes(): array
    {
        return ['status', 'project_id', 'week_start', 'project_role'];
    }
}
