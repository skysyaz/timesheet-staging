<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_legacy_admin_url_redirects_to_root(): void
    {
        $this->get('/admin')->assertRedirect('/');
        $this->get('/admin/login')->assertRedirect('/login');
        $this->get('/admin/weekly-hours')->assertRedirect('/weekly-hours');
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_livewire_login_redirects_without_server_error(): void
    {
        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'employee@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_access_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_dashboard_sidebar_renders_svg_navigation_icon(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('fi-sidebar-item fi-active', false);
        $response->assertSee('fi-sidebar-item-icon', false);
        $response->assertSee('M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25', false);
        $response->assertDontSee('corp-nav-x-icon', false);
    }

    public function test_admin_pages_use_cross_browser_favicons(): void
    {
        $this->generateFaviconAssets();

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('/branding/favicon-32x32.png', false);
        $response->assertSee('data-app-favicon', false);
        $response->assertSee('apple-touch-icon.png', false);
        $response->assertSee('site.webmanifest', false);
        $response->assertDontSee('<link rel="icon" href="' . asset('logo.jpg'), false);
        $response->assertDontSee('<link rel="icon" href="' . asset('logo.webp'), false);
    }

    public function test_favicon_ico_is_served_with_correct_content_type(): void
    {
        $this->generateFaviconAssets();

        $response = $this->get('/favicon.ico');

        $response->assertOk();
        $response->assertHeader('content-type', 'image/x-icon');
        $this->assertGreaterThan(0, (int) $response->headers->get('content-length'));
    }

    public function test_branding_favicon_png_is_served(): void
    {
        $this->generateFaviconAssets();

        $response = $this->get('/branding/favicon-32x32.png');

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
        $this->assertGreaterThan(0, (int) $response->headers->get('content-length'));
    }

    private function generateFaviconAssets(): void
    {
        $branding = public_path('branding');

        if (! is_dir($branding)) {
            mkdir($branding, 0755, true);
        }

        $png = public_path('branding/favicon-32x32.png');

        if (! is_file($png)) {
            $image = imagecreatetruecolor(32, 32);
            imagepng($image, $png);
        }

        if (! is_file(public_path('branding/favicon.ico'))) {
            copy($png, public_path('branding/favicon.ico'));
        }
    }
}
