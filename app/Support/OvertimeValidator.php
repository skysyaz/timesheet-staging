<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Validation\ValidationException;

class OvertimeValidator
{
    /**
     * @param  list<float|int|string>  $regularHours
     * @param  list<float|int|string>  $overtimeHours
     */
    public static function validate(array $regularHours, array $overtimeHours): void
    {
        $regular = self::normalizeWeek($regularHours);
        $overtime = self::normalizeWeek($overtimeHours);
        $dailyThreshold = Setting::overtimeDailyThreshold();
        $weeklyThreshold = Setting::standardWeeklyHours();
        $errors = [];

        foreach ($regular as $index => $hours) {
            $ot = $overtime[$index];
            $day = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$index];

            if ($hours < 0 || $hours > 24 || $ot < 0 || $ot > 24) {
                $errors["hours.{$index}"] = "{$day}: hours must be between 0 and 24.";
            }

            if (($hours + $ot) > 24) {
                $errors["hours.{$index}"] = "{$day}: regular and overtime combined cannot exceed 24 hours.";
            }

            if ($dailyThreshold !== null && $hours > $dailyThreshold) {
                $errors["hours.{$index}"] = "{$day}: regular hours cannot exceed the daily threshold of {$dailyThreshold}.";
            }
        }

        if (array_sum($regular) > $weeklyThreshold) {
            $errors['hours'] = "Regular hours cannot exceed the weekly threshold of {$weeklyThreshold}.";
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  list<float|int|string>  $hours
     * @return list<float>
     */
    public static function normalizeWeek(array $hours): array
    {
        return array_map(
            fn (mixed $value): float => (float) (filled($value) ? $value : 0),
            array_replace([0, 0, 0, 0, 0, 0, 0], array_values($hours)),
        );
    }
}
