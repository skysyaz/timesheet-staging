<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDailyHours implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail('Each day must be a valid number of hours.');

            return;
        }

        foreach ($value as $hours) {
            if (! is_numeric($hours)) {
                $fail('Each day must be a valid number of hours.');

                return;
            }

            if ((float) $hours < 0 || (float) $hours > 24) {
                $fail('Each day must be between 0 and 24 hours.');

                return;
            }
        }
    }
}
