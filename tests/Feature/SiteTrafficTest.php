<?php

namespace Tests\Feature;

use App\Filament\Widgets\SiteTrafficOverview;
use App\Models\SiteTrafficDaily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteTrafficTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_admin_page_view_is_recorded(): void
    {
        $this->get('/login')->assertOk();

        $this->assertDatabaseHas('site_traffic_daily', [
            'page_views' => 1,
            'unique_sessions' => 1,
        ]);
    }

    public function test_uptime_endpoints_are_not_counted_as_traffic(): void
    {
        config(['observability.uptime.enabled' => false]);

        $this->get('/up')->assertOk();

        $this->assertDatabaseMissing('site_traffic_daily', [
            'date' => now()->toDateString(),
        ]);
    }

    public function test_admin_can_see_traffic_widget_on_dashboard(): void
    {
        SiteTrafficDaily::create([
            'date' => now()->toDateString(),
            'page_views' => 42,
            'unique_sessions' => 7,
        ]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertTrue(SiteTrafficOverview::canView());

        Livewire::actingAs($admin)
            ->test(SiteTrafficOverview::class)
            ->assertSee('Site Traffic Today')
            ->assertSee('42');
    }

    public function test_non_admin_cannot_see_traffic_widget(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $this->actingAs($employee);

        $this->assertFalse(SiteTrafficOverview::canView());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Site Traffic Today');
    }
}
