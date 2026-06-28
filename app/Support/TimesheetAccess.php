<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TimesheetAccess
{
    public const SUMMARY_GROUP_BY = ['project', 'week', 'month', 'member'];

    public const TIMESHEET_STATUSES = [
        'draft',
        'pending_pm',
        'pending_pd',
        'approved',
        'rejected',
    ];

    public static function userCanEditTimesheet(User $user, Timesheet $timesheet): bool
    {
        if (! $timesheet->isEditable()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isEmployee()) {
            return $timesheet->user_id === $user->id;
        }

        return false;
    }

    public static function userCanRevertToDraft(User $user, Timesheet $timesheet): bool
    {
        return $user->isAdmin() && $timesheet->isApproved();
    }

    public static function userCanViewTimesheet(User $user, Timesheet $timesheet): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isEmployee()) {
            return $timesheet->user_id === $user->id;
        }

        if (self::userHasApprovalHistoryOnTimesheet($user, $timesheet)) {
            return true;
        }

        $timesheet->loadMissing('project');
        $project = $timesheet->project;

        if (! $project) {
            return false;
        }

        if ($user->isProjectManager()) {
            return $project->project_manager_id === $user->id;
        }

        if ($user->isProjectDirector()) {
            return $project->project_director_id === $user->id;
        }

        return false;
    }

    public static function userHasApprovalHistoryOnTimesheet(User $user, Timesheet $timesheet): bool
    {
        if ($timesheet->relationLoaded('approvalLogs')) {
            return $timesheet->approvalLogs->contains(
                fn ($log) => $log->user_id === $user->id,
            );
        }

        return $timesheet->approvalLogs()
            ->where('user_id', $user->id)
            ->exists();
    }

    public static function userCanAccessProject(User $user, ?Project $project): bool
    {
        if (! $project) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isEmployee()) {
            return true;
        }

        return self::userCanManageProject($user, $project);
    }

    public static function userCanViewProject(User $user, ?Project $project): bool
    {
        if (! $project) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isProjectManager() || $user->isProjectDirector();
    }

    public static function userCanEditProject(User $user, ?Project $project): bool
    {
        if (! $project) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isApprover()) {
            return false;
        }

        return $project->created_by === $user->id;
    }

    public static function userCanManageProject(User $user, ?Project $project): bool
    {
        if (! $project) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isProjectManager()) {
            return $project->project_manager_id === $user->id;
        }

        if ($user->isProjectDirector()) {
            return $project->project_director_id === $user->id;
        }

        return false;
    }

    public static function scopeTimesheetsForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isEmployee()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->isProjectManager() || $user->isProjectDirector()) {
            return $query->where(function (Builder $scopedQuery) use ($user): void {
                $scopedQuery
                    ->whereHas(
                        'project',
                        fn (Builder $projectQuery) => self::scopeAssignedProjectsForUser($projectQuery, $user),
                    )
                    ->orWhereHas(
                        'approvalLogs',
                        fn (Builder $logQuery) => $logQuery->where('user_id', $user->id),
                    );
            });
        }

        return $query->whereRaw('0 = 1');
    }

    public static function scopeProjectsForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isProjectManager() || $user->isProjectDirector()) {
            return $query;
        }

        if ($user->isEmployee()) {
            return $query->where('status', 'active');
        }

        return $query->whereRaw('0 = 1');
    }

    public static function scopeAssignedProjectsForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isEmployee()) {
            return $query->where('status', 'active');
        }

        if ($user->isProjectManager()) {
            return $query->where('project_manager_id', $user->id);
        }

        if ($user->isProjectDirector()) {
            return $query->where('project_director_id', $user->id);
        }

        return $query->whereRaw('0 = 1');
    }
}
