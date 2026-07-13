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

    protected static function booted(): void
    {
        // Ensure attachment files are removed with the timesheet. Deleting each
        // attachment through the model (rather than relying on the database
        // cascade) fires TimesheetAttachment's own cleanup that unlinks files.
        static::deleting(function (Timesheet $timesheet): void {
            $timesheet->attachments()->get()->each->delete();
        });
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

    public function attachments()
    {
        return $this->hasMany(TimesheetAttachment::class)->latest('id');
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
        $hours = $this->hours;

        if (! is_array($hours)) {
            return 0.0;
        }

        return array_sum($hours);
    }

    public function totalOvertimeHours(): float
    {
        $overtimeHours = $this->overtime_hours;

        if (! is_array($overtimeHours)) {
            return 0.0;
        }

        return array_sum($overtimeHours);
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

    public function isFutureWeek(): bool
    {
        // A week whose Monday has not arrived yet is a future week. Drafting
        // ahead is allowed, but hours not yet worked cannot be verified, so
        // submission and approval of a future week are blocked. The current
        // (in-progress) week is not a future week and remains submittable.
        return $this->week_start !== null && $this->week_start->isFuture();
    }

    public function canBeApprovedBy(User $user): bool
    {
        if ($this->isFutureWeek()) {
            return false;
        }

        // Segregation of duties: a user may not approve their own timesheet.
        // Admins are exempt (they manage the whole system and generally don't
        // submit timesheets), but approvers cannot self-approve their own hours.
        if (! $user->isAdmin() && $this->user_id === $user->id) {
            return false;
        }

        // Ensure project is loaded before checking manager status
        if (! $this->relationLoaded('project')) {
            $this->load('project');
        }

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
