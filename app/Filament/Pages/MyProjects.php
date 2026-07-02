<?php

namespace App\Filament\Pages;

use App\Models\Project;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MyProjects extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|\UnitEnum|null $navigationGroup = 'Time Tracking';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'My projects';

    protected static ?string $title = 'My projects';

    protected static ?string $slug = 'my-projects';

    protected string $view = 'filament.pages.my-projects';

    public static function canAccess(): bool
    {
        return auth()->user()?->isEmployee() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return Collection<int, array{
     *     project: Project,
     *     role: string,
     *     hours_logged: float,
     *     days_remaining: ?int,
     *     schedule_label: string,
     *     schedule_color: string,
     * }>
     */
    public function getAssignedProjects(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        return $user->projects()
            ->where('status', 'active')
            ->with(['members', 'projectManager', 'programManager'])
            ->orderBy('end_date')
            ->get()
            ->map(function (Project $project) use ($user): array {
                $health = $project->scheduleHealth();
                $hoursLogged = round($project->timesheets()
                    ->where('user_id', $user->id)
                    ->get()
                    ->sum(fn ($timesheet): float => $timesheet->totalHours()), 1);

                return [
                    'project' => $project,
                    'role' => $project->members->firstWhere('id', $user->id)?->pivot?->assigned_role ?? '—',
                    'hours_logged' => $hoursLogged,
                    'days_remaining' => $health->daysRemaining(),
                    'schedule_label' => $health->label(),
                    'schedule_color' => $health->color(),
                ];
            });
    }
}
