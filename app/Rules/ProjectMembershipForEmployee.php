<?php

namespace App\Rules;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Defends project_id server-side for employees. Dropdown options are scoped by
 * membership, but crafted requests can submit any id — reject those. When
 * editing, the timesheet's current project is still allowed (member may have
 * been removed since the row was created).
 */
class ProjectMembershipForEmployee implements ValidationRule
{
    public function __construct(
        private ?User $user = null,
        private ?Timesheet $existingTimesheet = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = $this->user ?? auth()->user();

        if (! $user || ! $user->isEmployee() || ! filled($value)) {
            return;
        }

        $project = Project::find((int) $value);

        if (! $project) {
            return;
        }

        $existing = $this->existingTimesheet ?? $this->timesheetFromRoute();

        if ($existing
            && (int) $existing->user_id === (int) $user->id
            && (int) $existing->project_id === (int) $value
        ) {
            return;
        }

        if (! $project->hasMember($user)) {
            $fail('You are not assigned to this project.');
        }
    }

    private function timesheetFromRoute(): ?Timesheet
    {
        $record = request()->route('record');

        if ($record instanceof Timesheet) {
            return $record;
        }

        if (filled($record)) {
            return Timesheet::query()->find($record);
        }

        return null;
    }
}
