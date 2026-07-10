<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Support\OvertimeValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OvertimeValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_week_passes_validation(): void
    {
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);

        $this->expectNotToPerformAssertions();

        OvertimeValidator::validate(
            [8, 8, 8, 8, 8, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
        );
    }

    public function test_combined_regular_and_overtime_over_24_hours_fails(): void
    {
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);

        $this->expectException(ValidationException::class);

        OvertimeValidator::validate(
            [20, 0, 0, 0, 0, 0, 0],
            [5, 0, 0, 0, 0, 0, 0],
        );
    }

    public function test_weekly_regular_hours_threshold_is_enforced(): void
    {
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);

        $this->expectException(ValidationException::class);

        OvertimeValidator::validate(
            [8, 8, 8, 8, 9, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
        );
    }

    public function test_daily_regular_hours_threshold_is_enforced_when_configured(): void
    {
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);
        Setting::create(['key' => 'overtimeDailyThreshold', 'value' => 8]);

        $this->expectException(ValidationException::class);

        OvertimeValidator::validate(
            [9, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
        );
    }

    public function test_negative_hours_fail_validation(): void
    {
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);

        $this->expectException(ValidationException::class);

        OvertimeValidator::validate(
            [-1, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
        );
    }

    public function test_normalize_week_pads_missing_days_with_zero(): void
    {
        $normalized = OvertimeValidator::normalizeWeek([8, 4]);

        $this->assertSame([8.0, 4.0, 0.0, 0.0, 0.0, 0.0, 0.0], $normalized);
    }
}
