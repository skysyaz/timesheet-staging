<?php

namespace App\Support;

class WeeklyHoursFormatter
{
    public static function display(float | int | string | null $hours): string
    {
        $hours = max(0, (float) ($hours ?: 0));
        $totalMinutes = (int) round($hours * 60);
        $wholeHours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $wholeHours, $minutes);
    }

    public static function parse(mixed $value): float
    {
        if (is_numeric($value)) {
            return max(0, min(24, (float) $value));
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        if (str_contains($value, ':')) {
            [$hoursPart, $minutesPart] = array_pad(explode(':', $value, 2), 2, '0');
            $hours = (float) $hoursPart + ((float) $minutesPart / 60);

            return max(0, min(24, $hours));
        }

        $parsed = (float) $value;

        return max(0, min(24, $parsed));
    }

    /**
     * @param  list<float|int|string|null>  $hours
     */
    public static function rowTotal(array $hours): float
    {
        return array_sum(array_map(
            fn (mixed $value): float => self::parse($value),
            array_replace([0, 0, 0, 0, 0, 0, 0], array_values($hours)),
        ));
    }

    /**
     * @param  list<array{hours: list<float|int|string|null>}>  $rows
     * @return list<float>
     */
    public static function columnTotals(array $rows): array
    {
        $totals = array_fill(0, 7, 0.0);

        foreach ($rows as $row) {
            foreach (array_values($row['hours'] ?? []) as $index => $value) {
                if ($index > 6) {
                    break;
                }

                $totals[$index] += self::parse($value);
            }
        }

        return $totals;
    }
}
