<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use App\Rules\ValidDailyHours;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WeeklyHoursSheet
{
    public function __construct(
        public User $user,
        public Carbon $weekStart,
    ) {}

    /**
     * @return list<array{
     *     id: ?int,
     *     project_id: ?int,
     *     project_name: string,
     *     activity: string,
     *     hours: list<float>,
     *     status: string,
     *     editable: bool,
     * }>
     */
    public function loadRows(): array
    {
        $timesheets = Timesheet::query()
            ->with('project')
            ->where('user_id', $this->user->id)
            ->whereDate('week_start', $this->weekStart->toDateString())
            ->orderBy('id')
            ->get();

        $rows = $timesheets->map(fn (Timesheet $timesheet): array => $this->rowFromTimesheet($timesheet))->all();

        if ($rows === []) {
            $rows[] = $this->emptyRow();
        }

        return $rows;
    }

    /**
     * @return list<array{
     *     id: ?int,
     *     project_id: ?int,
     *     project_name: string,
     *     activity: string,
     *     hours: list<float>,
     *     status: string,
     *     editable: bool,
     * }>
     */
    public function loadExportRows(): array
    {
        $timesheets = Timesheet::query()
            ->with('project')
            ->where('user_id', $this->user->id)
            ->whereDate('week_start', $this->weekStart->toDateString())
            ->orderBy('id')
            ->get();

        return $timesheets
            ->map(function (Timesheet $timesheet): array {
                $row = $this->rowFromTimesheet($timesheet);
                $row['project_name'] = $timesheet->project?->name ?? '—';

                return $row;
            })
            ->reject(fn (array $row): bool => $this->rowIsBlank($row))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function projectOptions(): array
    {
        $viewer = auth()->user();

        if ($viewer?->isAdmin()) {
            return Project::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();
        }

        $query = Project::query()
            ->where('status', 'active')
            ->orderBy('name');

        if ($this->user->isEmployee()) {
            $query->whereHas(
                'members',
                fn ($members) => $members->whereKey($this->user->id),
            );
        } else {
            TimesheetAccess::scopeAssignedProjectsForUser($query, $viewer);
        }

        $options = $query->pluck('name', 'id')->all();

        $existingProjectIds = Timesheet::query()
            ->where('user_id', $this->user->id)
            ->whereDate('week_start', $this->weekStart->toDateString())
            ->whereNotNull('project_id')
            ->pluck('project_id');

        if ($existingProjectIds->isNotEmpty()) {
            $existingOptions = Project::query()
                ->whereIn('id', $existingProjectIds)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();

            $options = $existingOptions + $options;
        }

        return $options;
    }

    /**
     * @param  list<array{
     *     id?: int|null,
     *     project_id?: int|null,
     *     activity?: string|null,
     *     hours?: list<float|int|string|null>,
     *     status?: string|null,
     *     editable?: bool|null,
     * }>  $rows
     */
    public function saveRows(array $rows, User $actor): void
    {
        $normalized = $this->normalizeIncomingRows($rows);
        $this->validateRows($normalized, $actor);

        DB::transaction(function () use ($normalized, $actor): void {
            $keptIds = [];

            foreach ($normalized as $row) {
                if ($this->rowIsBlank($row)) {
                    continue;
                }

                $timesheet = $this->persistRow($row, $actor);
                $keptIds[] = $timesheet->id;
            }

            $this->deleteRemovedDraftRows($keptIds, $actor);
        });
    }

    /**
     * @param  list<array{
     *     id?: int|null,
     *     project_id?: int|null,
     *     activity?: string|null,
     *     hours?: list<float|int|string|null>,
     *     status?: string|null,
     *     editable?: bool|null,
     * }>  $rows
     * @return list<array{
     *     id: ?int,
     *     project_id: ?int,
     *     activity: string,
     *     hours: list<float>,
     *     status: string,
     *     editable: bool,
     * }>
     */
    private function normalizeIncomingRows(array $rows): array
    {
        return array_values(array_map(function (array $row): array {
            $hours = array_map(
                fn (mixed $value): float => WeeklyHoursFormatter::parse($value),
                array_replace([0, 0, 0, 0, 0, 0, 0], array_values($row['hours'] ?? [])),
            );

            return [
                'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                'project_id' => filled($row['project_id'] ?? null) ? (int) $row['project_id'] : null,
                'activity' => trim((string) ($row['activity'] ?? '')),
                'hours' => $hours,
                'status' => (string) ($row['status'] ?? 'draft'),
                'editable' => (bool) ($row['editable'] ?? true),
            ];
        }, $rows));
    }

    /**
     * @param  list<array{
     *     id: ?int,
     *     project_id: ?int,
     *     activity: string,
     *     hours: list<float>,
     *     status: string,
     *     editable: bool,
     * }>  $rows
     */
    private function validateRows(array $rows, User $actor): void
    {
        $errors = [];
        $projectIds = [];

        foreach ($rows as $index => $row) {
            if ($this->rowIsBlank($row)) {
                continue;
            }

            if (! $row['project_id']) {
                if ($row['editable']) {
                    $errors["rows.{$index}.project_id"] = 'Select a project for this row.';
                }

                continue;
            }

            if (in_array($row['project_id'], $projectIds, true)) {
                $errors["rows.{$index}.project_id"] = 'Each project can only appear once in the same week.';
            }

            $projectIds[] = $row['project_id'];

            $conflictingTimesheet = Timesheet::query()
                ->where('user_id', $this->user->id)
                ->where('project_id', $row['project_id'])
                ->whereDate('week_start', $this->weekStart->toDateString())
                ->when($row['id'], fn ($query) => $query->where('id', '!=', $row['id']))
                ->first();

            if ($conflictingTimesheet && $row['id'] !== $conflictingTimesheet->id) {
                $canUpsertExisting = ! $row['id']
                    && $conflictingTimesheet->isEditable()
                    && TimesheetAccess::userCanEditTimesheet($actor, $conflictingTimesheet);

                if (! $canUpsertExisting) {
                    $errors["rows.{$index}.project_id"] = 'A timesheet row for this project already exists in this week.';

                    continue;
                }
            }

            if (! $row['editable']) {
                continue;
            }

            $validator = Validator::make(
                ['hours' => $row['hours']],
                ['hours' => [new ValidDailyHours()]],
            );

            if ($validator->fails()) {
                $errors["rows.{$index}.hours"] = $validator->errors()->first('hours');
            }

            if ($row['id']) {
                $timesheet = Timesheet::query()->find($row['id']);

                if (! $timesheet || $timesheet->user_id !== $this->user->id) {
                    $errors["rows.{$index}.id"] = 'This timesheet row could not be found.';

                    continue;
                }

                if (! TimesheetAccess::userCanEditTimesheet($actor, $timesheet)) {
                    $errors["rows.{$index}.id"] = 'This row cannot be edited in its current status.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array{
     *     id: ?int,
     *     project_id: ?int,
     *     activity: string,
     *     hours: list<float>,
     *     status: string,
     *     editable: bool,
     * }  $row
     */
    private function persistRow(array $row, User $actor): Timesheet
    {
        if ($row['id']) {
            $timesheet = Timesheet::query()->findOrFail($row['id']);
            $timesheet->update([
                'project_id' => $row['project_id'],
                'project_role' => $this->resolveProjectRole($timesheet, $row['project_id']),
                'hours' => $row['hours'],
                'tasks' => $this->tasksFromActivity($row['activity'], $row['hours'], $timesheet->tasks),
            ]);

            return $timesheet->fresh();
        }

        $existing = Timesheet::query()
            ->where('user_id', $this->user->id)
            ->where('project_id', $row['project_id'])
            ->whereDate('week_start', $this->weekStart->toDateString())
            ->first();

        if ($existing) {
            $existing->update([
                'project_role' => $this->resolveProjectRole($existing, $row['project_id']),
                'hours' => $row['hours'],
                'tasks' => $this->tasksFromActivity($row['activity'], $row['hours'], $existing->tasks),
            ]);

            return $existing->fresh();
        }

        return Timesheet::query()->create([
            'user_id' => $this->user->id,
            'project_id' => $row['project_id'],
            'project_role' => $this->assignedProjectRole($row['project_id']),
            'week_start' => $this->weekStart->toDateString(),
            'hours' => $row['hours'],
            'tasks' => $this->tasksFromActivity($row['activity'], $row['hours'], null),
            'status' => 'draft',
        ]);
    }

    /**
     * @param  list<int>  $keptIds
     */
    private function deleteRemovedDraftRows(array $keptIds, User $actor): void
    {
        $query = Timesheet::query()
            ->where('user_id', $this->user->id)
            ->whereDate('week_start', $this->weekStart->toDateString())
            ->whereIn('status', ['draft', 'rejected']);

        if ($keptIds !== []) {
            $query->whereNotIn('id', $keptIds);
        }

        $query->get()->each(function (Timesheet $timesheet) use ($actor): void {
            if (TimesheetAccess::userCanEditTimesheet($actor, $timesheet)) {
                $timesheet->delete();
            }
        });
    }

    private function rowFromTimesheet(Timesheet $timesheet): array
    {
        return [
            'id' => $timesheet->id,
            'project_id' => $timesheet->project_id,
            'project_name' => $timesheet->project?->name ?? '',
            'activity' => $this->activityFromTasks($timesheet->tasks),
            'hours' => array_map(
                fn (mixed $value): float => (float) $value,
                array_replace([0, 0, 0, 0, 0, 0, 0], $timesheet->hours ?? []),
            ),
            'status' => $timesheet->status,
            'editable' => $timesheet->isEditable(),
        ];
    }

    /**
     * @return array{
     *     id: null,
     *     project_id: null,
     *     project_name: string,
     *     activity: string,
     *     hours: list<float>,
     *     status: string,
     *     editable: bool,
     * }
     */
    private function emptyRow(): array
    {
        return [
            'id' => null,
            'project_id' => null,
            'project_name' => '',
            'activity' => '',
            'hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
            'editable' => true,
        ];
    }

    /**
     * @param  array{
     *     id: ?int,
     *     project_id: ?int,
     *     activity: string,
     *     hours: list<float>,
     * }  $row
     */
    private function rowIsBlank(array $row): bool
    {
        if ($row['project_id']) {
            return false;
        }

        if ($row['activity'] !== '') {
            return false;
        }

        return WeeklyHoursFormatter::rowTotal($row['hours']) <= 0;
    }

    private function activityFromTasks(?array $tasks): string
    {
        $parts = array_values(array_unique(array_filter(array_map(
            fn (mixed $value): string => trim((string) $value),
            array_replace(['', '', '', '', '', '', ''], $tasks ?? []),
        ))));

        return implode(', ', $parts);
    }

    /**
     * @param  list<float>  $hours
     * @param  list<string>|null  $existingTasks
     * @return list<string>
     */
    private function tasksFromActivity(string $activity, array $hours, ?array $existingTasks): array
    {
        $tasks = array_replace(['', '', '', '', '', '', ''], $existingTasks ?? []);
        $activity = trim($activity);

        for ($i = 0; $i < 7; $i++) {
            if ($hours[$i] > 0) {
                $tasks[$i] = $activity;
            } elseif ($activity === '' && ($existingTasks[$i] ?? '') !== '') {
                $tasks[$i] = '';
            }
        }

        return $tasks;
    }

    private function resolveProjectRole(Timesheet $timesheet, ?int $projectId): ?string
    {
        if (filled($timesheet->project_role)) {
            return $timesheet->project_role;
        }

        return $this->assignedProjectRole($projectId);
    }

    private function assignedProjectRole(?int $projectId): ?string
    {
        if (! $projectId) {
            return null;
        }

        $assignedRole = $this->user->projects()
            ->whereKey($projectId)
            ->value('project_user.assigned_role');

        return filled($assignedRole) ? (string) $assignedRole : null;
    }
}
