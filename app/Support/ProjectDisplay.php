<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Timesheet;

class ProjectDisplay
{
    /**
     * @return list<string> Lowercase trimmed project names that appear on more than one project.
     */
    public static function ambiguousNameKeys(): array
    {
        return once(function (): array {
            return Project::query()
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->pluck('name')
                ->map(fn (mixed $name): string => mb_strtolower(trim((string) $name)))
                ->countBy()
                ->filter(fn (int $count): bool => $count > 1)
                ->keys()
                ->values()
                ->all();
        });
    }

    public static function listLabel(?Project $project, ?array $ambiguousNameKeys = null): string
    {
        $ambiguousNameKeys ??= self::ambiguousNameKeys();

        if ($project === null) {
            return __('Project unavailable (Name unavailable)');
        }

        $code = trim((string) $project->code);
        $name = trim((string) $project->name);

        if ($name === '') {
            if ($code !== '') {
                return __(':code (Name unavailable)', ['code' => $code]);
            }

            return __('Unknown project (Name unavailable)');
        }

        $normalizedName = mb_strtolower($name);
        $label = in_array($normalizedName, $ambiguousNameKeys, true) && $code !== ''
            ? "{$name} ({$code})"
            : $name;

        if ($project->status !== 'active') {
            $status = __(ucfirst(str_replace('_', ' ', $project->status)));

            return "{$label} ({$status})";
        }

        return $label;
    }

    public static function listLabelForTimesheet(Timesheet $timesheet, ?array $ambiguousNameKeys = null): string
    {
        $timesheet->loadMissing('project');

        return self::listLabel($timesheet->project, $ambiguousNameKeys);
    }
}
