<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Timesheet;
use App\Support\ProjectDisplay;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TimesheetSummaryBuilder
{
    public function __construct(
        public string $groupBy = 'project',
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $projectId = null,
        public ?int $userId = null,
        public ?string $status = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromValidated($request->all());
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            groupBy: $validated['groupBy'] ?? 'project',
            dateFrom: $validated['dateFrom'] ?? null,
            dateTo: $validated['dateTo'] ?? null,
            projectId: isset($validated['projectId']) ? (int) $validated['projectId'] : null,
            userId: isset($validated['userId']) ? (int) $validated['userId'] : null,
            status: $validated['status'] ?? null,
        );
    }

    public static function fromTableFilters(?array $filters): self
    {
        return new self(
            groupBy: 'project',
            projectId: self::filterIntValue($filters, 'project_id'),
            userId: self::filterIntValue($filters, 'user_id'),
            status: self::filterStringValue($filters, 'status'),
        );
    }

    public static function fromReports(
        string $reportType,
        ?string $dateFrom,
        ?string $dateTo,
        ?int $projectId,
    ): self {
        $groupBy = in_array($reportType, TimesheetAccess::SUMMARY_GROUP_BY, true)
            ? $reportType
            : 'project';

        return new self(
            groupBy: $groupBy,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            projectId: $projectId,
        );
    }

    public function toQueryParams(): array
    {
        return array_filter([
            'groupBy' => $this->groupBy !== 'project' ? $this->groupBy : null,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'projectId' => $this->projectId,
            'userId' => $this->userId,
            'status' => $this->status,
        ], fn ($value) => filled($value));
    }

    public function exportUrl(): string
    {
        return route('pdf.summary', $this->toQueryParams());
    }

    public function query(): Builder
    {
        $user = auth()->user();
        $query = Timesheet::query()->with('project', 'user');

        TimesheetAccess::scopeTimesheetsForUser($query, $user);

        if ($this->userId && ! $user->isEmployee()) {
            $query->where('user_id', $this->userId);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom) {
            $query->where('week_start', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('week_start', '<=', $this->dateTo);
        }

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        return $query;
    }

    /**
     * @return array<int, array{label: string, hours: float|int}>
     */
    public function groupedData(): array
    {
        $timesheets = $this->query()->get();

        return match ($this->groupBy) {
            'week' => $this->groupByWeek($timesheets),
            'month' => $this->groupByMonth($timesheets),
            'member' => $this->groupByMember($timesheets),
            default => $this->groupByProject($timesheets),
        };
    }

    public function totalHours(): float
    {
        return array_sum(array_column($this->groupedData(), 'hours'));
    }

    public function resolvedProject(): ?Project
    {
        return $this->projectId ? Project::find($this->projectId) : null;
    }

    public function periodLabel(): string
    {
        if ($this->dateFrom && $this->dateTo) {
            $from = Carbon::parse($this->dateFrom);
            $to = Carbon::parse($this->dateTo);

            if ($from->isSameMonth($to) && $from->isSameYear($to)) {
                return $from->format('F Y');
            }

            return $from->format('d M Y') . ' – ' . $to->format('d M Y');
        }

        return now()->format('F Y');
    }

    public function dataColumnLabel(): string
    {
        return match ($this->groupBy) {
            'week' => 'Week',
            'month' => 'Month',
            'member' => 'Member',
            default => 'Project',
        };
    }

    public function title(): string
    {
        return match ($this->groupBy) {
            'week' => 'WEEKLY SUMMARY',
            'month' => 'MONTHLY SUMMARY',
            'member' => 'MEMBER SUMMARY',
            default => 'PROJECT SUMMARY',
        };
    }

    public function statusLabel(): ?string
    {
        if (! $this->status) {
            return null;
        }

        return ucwords(str_replace('_', ' ', $this->status));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Timesheet>  $timesheets
     * @return array<int, array{label: string, hours: float|int}>
     */
    private function groupByMember($timesheets): array
    {
        $results = [];

        foreach ($timesheets as $timesheet) {
            $key = $timesheet->user?->name ?? 'Unknown';
            $results[$key] = ($results[$key] ?? 0) + $timesheet->totalHours();
        }

        arsort($results);

        return array_values(array_map(
            fn ($hours, $label) => ['label' => $label, 'hours' => $hours],
            $results,
            array_keys($results),
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Timesheet>  $timesheets
     * @return array<int, array{label: string, hours: float|int}>
     */
    private function groupByProject($timesheets): array
    {
        $results = [];

        foreach ($timesheets as $timesheet) {
            $key = ProjectDisplay::listLabel($timesheet->project);
            $results[$key] = ($results[$key] ?? 0) + $timesheet->totalHours();
        }

        arsort($results);

        return array_values(array_map(
            fn ($hours, $label) => ['label' => $label, 'hours' => $hours],
            $results,
            array_keys($results),
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Timesheet>  $timesheets
     * @return array<int, array{label: string, hours: float|int}>
     */
    private function groupByWeek($timesheets): array
    {
        $results = [];

        foreach ($timesheets as $timesheet) {
            $weekStart = $timesheet->week_start instanceof Carbon
                ? $timesheet->week_start
                : Carbon::parse($timesheet->week_start);
            $weekEnd = $weekStart->copy()->addDays(6)->format('d/m/Y');
            $key = $weekStart->format('Y-\WW') . ' (ending ' . $weekEnd . ')';
            $results[$key] = ($results[$key] ?? 0) + $timesheet->totalHours();
        }

        ksort($results);

        return array_values(array_map(
            fn ($hours, $label) => ['label' => $label, 'hours' => $hours],
            $results,
            array_keys($results),
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Timesheet>  $timesheets
     * @return array<int, array{label: string, hours: float|int}>
     */
    private function groupByMonth($timesheets): array
    {
        $results = [];

        foreach ($timesheets as $timesheet) {
            $weekStart = $timesheet->week_start instanceof Carbon
                ? $timesheet->week_start
                : Carbon::parse($timesheet->week_start);
            $key = $weekStart->format('M Y');
            $results[$key] = ($results[$key] ?? 0) + $timesheet->totalHours();
        }

        ksort($results);

        return array_values(array_map(
            fn ($hours, $label) => ['label' => $label, 'hours' => $hours],
            $results,
            array_keys($results),
        ));
    }

    private static function filterStringValue(?array $filters, string $key): ?string
    {
        $value = self::filterRawValue($filters, $key);

        return filled($value) ? (string) $value : null;
    }

    private static function filterIntValue(?array $filters, string $key): ?int
    {
        $value = self::filterRawValue($filters, $key);

        return filled($value) ? (int) $value : null;
    }

    private static function filterRawValue(?array $filters, string $key): mixed
    {
        if (empty($filters[$key])) {
            return null;
        }

        $state = $filters[$key];

        if (! is_array($state)) {
            return $state;
        }

        return $state['value'] ?? $state['values'] ?? null;
    }
}
