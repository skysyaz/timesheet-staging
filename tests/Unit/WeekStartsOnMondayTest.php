<?php

namespace Tests\Unit;

use App\Rules\WeekStartsOnMonday;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class WeekStartsOnMondayTest extends TestCase
{
    public function test_accepts_monday(): void
    {
        $rule = new WeekStartsOnMonday;
        $failed = false;

        $rule->validate('week_start', Carbon::parse('2026-06-29')->toDateString(), function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_rejects_non_monday(): void
    {
        $rule = new WeekStartsOnMonday;
        $message = null;

        $rule->validate('week_start', Carbon::parse('2026-06-30')->toDateString(), function (string $msg) use (&$message) {
            $message = $msg;
        });

        $this->assertSame('Week start must be a Monday.', $message);
    }
}
