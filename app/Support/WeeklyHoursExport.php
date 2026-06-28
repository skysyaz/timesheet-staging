<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WeeklyHoursExport
{
    public function __construct(
        public User $user,
        public Carbon $weekStart,
    ) {}

    public static function fromRequest(Request $request, User $viewer): self
    {
        $validated = $request->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'weekStart' => ['required', 'date'],
        ]);

        $user = User::query()->findOrFail($validated['userId']);

        return self::for($user, $validated['weekStart'], $viewer);
    }

    public static function for(User $user, Carbon | string $weekStart, User $viewer): self
    {
        $weekStart = Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY);

        if (! WeeklyHoursAccess::userCanView($viewer, $user)) {
            abort(403);
        }

        return new self($user, $weekStart);
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
    public function rows(): array
    {
        return $this->sheet()->loadExportRows();
    }

    /**
     * @return array<int, string>
     */
    public function dayHeaders(): array
    {
        return collect(range(0, 6))
            ->mapWithKeys(function (int $offset): array {
                $date = $this->weekStart->copy()->addDays($offset);

                return [$offset => strtoupper($date->format('D j'))];
            })
            ->all();
    }

    public function weekLabel(): string
    {
        return $this->weekStart->format('F Y') . ' – Week ' . $this->weekStart->isoWeek();
    }

    /**
     * @return list<string>
     */
    public function columnTotals(): array
    {
        return array_map(
            fn (float $total): string => WeeklyHoursFormatter::display($total),
            WeeklyHoursFormatter::columnTotals($this->rows()),
        );
    }

    public function grandTotal(): string
    {
        $total = array_sum(WeeklyHoursFormatter::columnTotals($this->rows()));

        return WeeklyHoursFormatter::display($total);
    }

    public function formatHours(float | int | string | null $hours): string
    {
        return WeeklyHoursFormatter::display($hours);
    }

    public function rowDuration(array $row): string
    {
        return WeeklyHoursFormatter::display(WeeklyHoursFormatter::rowTotal($row['hours'] ?? []));
    }

    public function filename(): string
    {
        return sprintf(
            'weekly_hours_%s_%s.pdf',
            str($this->user->name)->slug('_'),
            $this->weekStart->format('Y-m-d'),
        );
    }

    private function sheet(): WeeklyHoursSheet
    {
        return new WeeklyHoursSheet($this->user, $this->weekStart);
    }
}
