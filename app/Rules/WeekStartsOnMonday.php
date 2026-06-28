<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WeekStartsOnMonday implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable) {
            $fail('Please enter a valid date.');

            return;
        }

        if ($date->dayOfWeek !== Carbon::MONDAY) {
            $fail('Week start must be a Monday.');
        }
    }
}
