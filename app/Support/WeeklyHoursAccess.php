<?php

namespace App\Support;

use App\Models\User;

class WeeklyHoursAccess
{
    public static function userCanView(User $viewer, User $target): bool
    {
        if ($viewer->isAdmin()) {
            return true;
        }

        if ($viewer->isEmployee()) {
            return $viewer->id === $target->id;
        }

        return User::query()
            ->whereKey($target->id)
            ->where(function ($userQuery) use ($viewer): void {
                $userQuery
                    ->whereHas(
                        'timesheets.project',
                        fn ($projectQuery) => TimesheetAccess::scopeAssignedProjectsForUser($projectQuery, $viewer),
                    )
                    ->orWhere('id', $viewer->id);
            })
            ->exists();
    }
}
