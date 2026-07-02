<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TimesheetResource;
use App\Models\Timesheet;
use Filament\Widgets\Widget;

class WelcomeBanner extends Widget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.welcome-banner';

    protected const TIMEZONE = 'Asia/Kuala_Lumpur';

    public function getGreeting(): string
    {
        $hour = (int) now(self::TIMEZONE)->format('H');

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    public function getTodayLabel(): string
    {
        return now(self::TIMEZONE)->format('l, F j, Y');
    }

    public function getPendingCount(): int
    {
        $user = auth()->user();
        $query = Timesheet::query()->whereIn('status', ['pending_pm', 'pending_program_manager']);

        if ($user->isEmployee()) {
            return $query->where('user_id', $user->id)->count();
        }

        if ($user->canApproveAsPm()) {
            return (clone $query)->where('status', 'pending_pm')->count();
        }

        if ($user->canApproveAsProgramManager()) {
            return (clone $query)->where('status', 'pending_program_manager')->count();
        }

        return $query->count();
    }

    public function getCreateUrl(): string
    {
        return TimesheetResource::getUrl('create');
    }

    public function getTimesheetsUrl(): string
    {
        return TimesheetResource::getUrl('index');
    }
}
