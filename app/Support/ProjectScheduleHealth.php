<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Setting;
use Carbon\Carbon;

class ProjectScheduleHealth
{
    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_ON_TRACK = 'on_track';

    public const STATUS_AT_RISK = 'at_risk';

    public const STATUS_DELAYED = 'delayed';

    public const STATUS_COMPLETED = 'completed';

    public function __construct(public Project $project) {}

    public function durationDays(): ?int
    {
        if (! $this->project->start_date || ! $this->project->end_date) {
            return null;
        }

        return $this->project->start_date->diffInDays($this->project->end_date) + 1;
    }

    public function daysElapsed(): ?int
    {
        if (! $this->project->start_date) {
            return null;
        }

        $today = now()->startOfDay();

        if ($today->lt($this->project->start_date)) {
            return 0;
        }

        if ($this->project->end_date && $today->gt($this->project->end_date)) {
            return $this->durationDays();
        }

        return $this->project->start_date->diffInDays($today) + 1;
    }

    public function daysRemaining(): ?int
    {
        if (! $this->project->end_date) {
            return null;
        }

        $today = now()->startOfDay();

        if ($today->gt($this->project->end_date)) {
            return 0;
        }

        return $today->diffInDays($this->project->end_date);
    }

    public function hoursLogged(): float
    {
        return (float) $this->project->timesheets()
            ->get()
            ->sum(fn ($timesheet): float => $timesheet->totalHours());
    }

    public function expectedHoursToDate(): float
    {
        $daysElapsed = $this->daysElapsed();

        if ($daysElapsed === null || $daysElapsed <= 0) {
            return 0.0;
        }

        $memberCount = max(1, $this->project->members()->count());
        $weeklyHours = Setting::standardWeeklyHours();
        $weeksElapsed = $daysElapsed / 7;

        return round($memberCount * $weeklyHours * $weeksElapsed, 1);
    }

    public function timeProgressPercent(): ?float
    {
        $duration = $this->durationDays();
        $elapsed = $this->daysElapsed();

        if ($duration === null || $elapsed === null || $duration <= 0) {
            return null;
        }

        return min(100, round(($elapsed / $duration) * 100, 1));
    }

    public function hoursProgressPercent(): ?float
    {
        $expected = $this->expectedHoursToDate();

        if ($expected <= 0) {
            return null;
        }

        return min(100, round(($this->hoursLogged() / $expected) * 100, 1));
    }

    public function status(): string
    {
        if (! $this->project->start_date || ! $this->project->end_date) {
            return self::STATUS_NOT_STARTED;
        }

        $today = now()->startOfDay();

        if ($today->lt($this->project->start_date)) {
            return self::STATUS_NOT_STARTED;
        }

        if ($this->project->status === 'archived') {
            return self::STATUS_COMPLETED;
        }

        if ($today->gt($this->project->end_date)) {
            return self::STATUS_DELAYED;
        }

        $timeProgress = ($this->timeProgressPercent() ?? 0) / 100;
        $hoursProgress = ($this->hoursProgressPercent() ?? 0) / 100;
        $daysRemaining = $this->daysRemaining() ?? 0;

        if ($timeProgress >= 0.5 && $hoursProgress < 0.5) {
            return self::STATUS_AT_RISK;
        }

        if ($daysRemaining <= 7 && $hoursProgress < 0.85) {
            return self::STATUS_AT_RISK;
        }

        return self::STATUS_ON_TRACK;
    }

    public function label(): string
    {
        return match ($this->status()) {
            self::STATUS_NOT_STARTED => 'Not started',
            self::STATUS_ON_TRACK => 'On track',
            self::STATUS_AT_RISK => 'At risk',
            self::STATUS_DELAYED => 'Delayed',
            self::STATUS_COMPLETED => 'Completed',
            default => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this->status()) {
            self::STATUS_ON_TRACK => 'success',
            self::STATUS_AT_RISK => 'warning',
            self::STATUS_DELAYED => 'danger',
            self::STATUS_COMPLETED => 'gray',
            default => 'info',
        };
    }
}
