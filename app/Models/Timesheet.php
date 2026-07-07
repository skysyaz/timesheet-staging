<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditableChanges;
use App\Models\Setting;
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
        'overtime_hours',
        'tasks',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'hours' => 'array',
            'overtime_hours' => 'array',
            'tasks' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
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

    public function programManagerApproverName(): string
    {
        return $this->latestApprovalLog('approved_program_manager')?->user?->name ?? '';
    }

    public function programManagerApproverDate(): string
    {
        return $this->latestApprovalLog('approved_program_manager')?->created_at?->format('d/m/Y') ?? '';
    }

    public function totalRegularHours(): float
    {
        return array_sum($this->hours ?? [0, 0, 0, 0, 0, 0, 0]);
    }

    public function totalOvertimeHours(): float
    {
        return array_sum($this->overtime_hours ?? [0, 0, 0, 0, 0, 0, 0]);
    }

    public function totalHours(): float
    {
        return $this->totalRegularHours() + $this->totalOvertimeHours();
    }

    public function weightedHours(): float
    {
        $rate = Setting::overtimeRate();

        return $this->totalRegularHours() + ($this->totalOvertimeHours() * $rate);
    }

    public function taskForDay(int $index): string
    {
        $tasks = $this->tasks ?? [];
        $task = trim((string) ($tasks[$index] ?? ''));

        if ($task !== '') {
            return $task;
        }

        $hours = (float) ($this->hours[$index] ?? 0);
        $overtime = (float) ($this->overtime_hours[$index] ?? 0);

        if (($hours + $overtime) > 0 && filled($this->notes)) {
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

    public function isPendingProgramManager(): bool
    {
        return $this->status === 'pending_program_manager';
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
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function isSubmittable(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function canBeApprovedBy(User $user): bool
    {
        $this->loadMissing('project');
        $project = $this->project;

        if ($this->isPendingPm()) {
            return $project?->isManagedBy($user) ?? false;
        }

        if ($this->isPendingProgramManager()) {
            return $project?->isLedByProgramManager($user) ?? false;
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
