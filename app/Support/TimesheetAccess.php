<?php

namespace App\Support;

use App\Enums\TimesheetStatus;
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
        'pending_program_manager',
        'approved',
        'rejected',
    ];

    public static function statusOptions(): array
    {
        return TimesheetStatus::options();
    }

    public static function statusValues(): array
    {
        return TimesheetStatus::values();
    }

    public static function userCanEditTimesheet(User $user, Timesheet $timesheet): bool
    {
        if (! $timesheet->isEditable()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $timesheet->user_id === $user->id;
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

        // Owners always see their own rows (including PMs/program managers
        // logging hours on projects they do not manage).
        if ($timesheet->user_id === $user->id) {
            return true;
        }

        if ($user->isEmployee()) {
            return false;
        }

        if (self::userHasApprovalHistoryOnTimesheet($user, $timesheet)) {
            return true;
        }

        // Ensure project is loaded for authorization checks
        if (! $timesheet->relationLoaded('project')) {
            $timesheet->load('project');
        }

        $project = $timesheet->project;

        if (! $project) {
            return false;
        }

        if ($user->isProjectManager()) {
            return $project->project_manager_id === $user->id;
        }

        if ($user->isProgramManager()) {
            return $project->program_manager_id === $user->id;
        }

        if ($user->isProjectAdmin()) {
            return true;
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

        if ($user->isAdmin() || $user->isProjectAdmin()) {
            return true;
        }

        return $user->isProjectManager() || $user->isProgramManager();
    }

    public static function userCanEditProject(User $user, ?Project $project): bool
    {
        if (! $project) {
            return false;
        }

        if ($user->isAdmin() || $user->isProjectAdmin()) {
            return true;
        }

        if (! $user->isApprover()) {
            return false;
        }

        return self::userCanManageProject($user, $project)
            || $project->created_by === $user->id;
    }

    public static function userCanManageProject(User $user, ?Project $project): bool
    {
        if (! $project) {
            return false;
        }

        if ($user->isAdmin() || $user->isProjectAdmin()) {
            return true;
        }

        if ($user->isProjectManager()) {
            return $project->project_manager_id === $user->id;
        }

        if ($user->isProgramManager()) {
            return $project->program_manager_id === $user->id;
        }

        return false;
    }

    public static function scopeTimesheetsForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isProjectAdmin()) {
            return $query;
        }

        if ($user->isEmployee()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->isProjectManager() || $user->isProgramManager()) {
            // Deliberate, not a leak: an approver who has acted on any of a
            // user's timesheets keeps read access to that user's hours on the
            // same project (the approvalLogs clause), in addition to all
            // timesheets on projects they manage. Confirmed as intended
            // business rule — do not tighten this to the single approved row.
            // Also include the approver's own timesheets so they can edit/view
            // hours they logged on projects they do not manage.
            return $query->where(function (Builder $scopedQuery) use ($user): void {
                $scopedQuery
                    ->where('user_id', $user->id)
                    ->orWhereHas(
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
        if ($user->isAdmin() || $user->isProjectManager() || $user->isProgramManager() || $user->isProjectAdmin()) {
            return $query;
        }

        if ($user->isEmployee()) {
            return $query->where('status', 'active');
        }

        return $query->whereRaw('0 = 1');
    }

    public static function scopeAssignedProjectsForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isProjectAdmin()) {
            return $query;
        }

        if ($user->isEmployee()) {
            return $query->where('status', 'active');
        }

        if ($user->isProjectManager()) {
            return $query->where('project_manager_id', $user->id);
        }

        if ($user->isProgramManager()) {
            return $query->where('program_manager_id', $user->id);
        }

        return $query->whereRaw('0 = 1');
    }

    /**
     * @return array<int, string>
     */
    public static function assignableUserOptionsForAdmin(): array
    {
        return User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function userFilterOptionsForViewer(?User $viewer): array
    {
        if (! $viewer || $viewer->isEmployee()) {
            return [];
        }

        if ($viewer->isAdmin()) {
            return self::assignableUserOptionsForAdmin();
        }

        $query = User::query()->orderBy('name');

        if ($viewer->isProjectAdmin()) {
            UserAccess::scopeVisibleUsers($query, $viewer);
        } else {
            $query->where(function (Builder $userQuery) use ($viewer): void {
                $userQuery
                    ->whereHas(
                        'timesheets.project',
                        fn (Builder $projectQuery) => self::scopeAssignedProjectsForUser($projectQuery, $viewer),
                    )
                    ->orWhere('id', $viewer->id);
            });
        }

        return $query->pluck('name', 'id')->all();
    }
}
