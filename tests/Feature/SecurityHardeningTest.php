<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    }

    public function test_csp_report_only_header_is_present_when_enabled(): void
    {
        config(['security.csp_report_only' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_csp_enforcing_header_is_present_when_enabled(): void
    {
        config([
            'security.csp_report_only' => false,
            'security.csp_enforce' => true,
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; img-src 'self' data: blob:; font-src 'self' data: https://fonts.bunny.net; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    }

    public function test_security_txt_is_publicly_accessible(): void
    {
        config(['security.contact' => 'security@skysyaz.my']);

        $response = $this->get('/.well-known/security.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Contact: mailto:security@skysyaz.my');
        $response->assertSee('Expires:');
    }

    public function test_health_check_is_allowed_in_testing_environment(): void
    {
        $response = $this->getJson('/up');

        $response->assertOk();
        $response->assertJson(['status' => 'up']);
    }

    public function test_health_check_is_blocked_in_production_for_external_ips(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->call(
            'GET',
            '/up',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.1',
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        $response->assertNotFound();
    }

    public function test_health_check_allows_localhost_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->getJson('/up');

        $response->assertOk();
        $response->assertJson(['status' => 'up']);
    }

    public function test_user_with_valid_role_can_access_panel(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->assertTrue($employee->canAccessPanel(filament()->getCurrentOrDefaultPanel()));
    }

    public function test_user_with_invalid_role_cannot_access_panel(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $user->forceFill(['role' => 'invalid'])->save();

        $this->assertFalse($user->canAccessPanel(filament()->getCurrentOrDefaultPanel()));
    }

    public function test_multi_factor_authentication_is_enabled_on_panel(): void
    {
        $this->assertTrue(filament()->hasMultiFactorAuthentication());
        $this->assertArrayHasKey('app', filament()->getMultiFactorAuthenticationProviders());
    }

    public function test_user_model_supports_app_authentication(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication::class,
            $user,
        );
    }
}
