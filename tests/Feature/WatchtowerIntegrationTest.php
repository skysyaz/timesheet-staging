<?php

namespace Tests\Feature;

use App\Support\WatchtowerReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class WatchtowerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_watchtower_reporting_defaults_off_without_credentials(): void
    {
        config([
            'services.watchtower.url' => null,
            'services.watchtower.token' => null,
        ]);

        Http::fake();

        app(WatchtowerReporter::class)->report(new RuntimeException('should not send'));

        Http::assertNothingSent();
    }

    public function test_watchtower_should_report_filters_noise_like_flare(): void
    {
        $reporter = app(WatchtowerReporter::class);

        $this->assertFalse($reporter->shouldReport(new NotFoundHttpException()));
        $this->assertFalse($reporter->shouldReport(new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException()));
        $this->assertTrue($reporter->shouldReport(new RuntimeException('server error')));
    }

    public function test_watchtower_reporter_posts_errors_when_configured(): void
    {
        Http::fake([
            'https://log.skysyaz.my/api/errors' => Http::response(['ok' => true], 201),
        ]);

        config([
            'services.watchtower.url' => 'https://log.skysyaz.my',
            'services.watchtower.token' => 'test-watchtower-token',
            'services.watchtower.app_name' => 'timesheet',
        ]);

        app(WatchtowerReporter::class)->report(new RuntimeException('Watchtower integration test'), [
            'source' => 'test',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://log.skysyaz.my/api/errors'
                && $request->hasHeader('Authorization', 'Bearer test-watchtower-token')
                && $request['app_name'] === 'timesheet'
                && $request['message'] === 'Watchtower integration test'
                && $request['context']['source'] === 'test'
                && $request['context']['exception_class'] === RuntimeException::class;
        });
    }

    public function test_watchtower_reporter_never_throws_when_watchtower_is_down(): void
    {
        Http::fake([
            'https://log.skysyaz.my/api/errors' => Http::response(null, 503),
        ]);

        config([
            'services.watchtower.url' => 'https://log.skysyaz.my',
            'services.watchtower.token' => 'test-watchtower-token',
        ]);

        app(WatchtowerReporter::class)->report(new RuntimeException('outage test'));

        $this->assertTrue(true);
    }
}
