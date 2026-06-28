<?php

namespace Tests\Unit;

use App\Filament\Widgets\WelcomeBanner;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class WelcomeBannerGreetingTest extends TestCase
{
    public function test_morning_greeting_in_malaysia_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-26 09:00:00', 'Asia/Kuala_Lumpur'));

        $widget = new WelcomeBanner;

        $this->assertSame('Good morning', $widget->getGreeting());
        $this->assertSame('Friday, June 26, 2026', $widget->getTodayLabel());
    }

    public function test_afternoon_greeting_in_malaysia_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-26 14:30:00', 'Asia/Kuala_Lumpur'));

        $widget = new WelcomeBanner;

        $this->assertSame('Good afternoon', $widget->getGreeting());
    }

    public function test_evening_greeting_in_malaysia_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-26 19:00:00', 'Asia/Kuala_Lumpur'));

        $widget = new WelcomeBanner;

        $this->assertSame('Good evening', $widget->getGreeting());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
