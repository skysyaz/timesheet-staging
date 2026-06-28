<?php

namespace Tests\Feature;

use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\FlareClient\Enums\FlareEntityType;
use Spatie\LaravelFlare\Jobs\SendFlarePayload;
use Spatie\LaravelFlare\Senders\LaravelHttpSender;
use Tests\TestCase;

class FlareIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_flare_config_redacts_timesheet_sensitive_fields(): void
    {
        $fields = config('flare.censor.body_fields');

        $this->assertContains('hours', $fields);
        $this->assertContains('tasks', $fields);
        $this->assertContains('notes', $fields);
        $this->assertContains('password', $fields);
        $this->assertContains('Authorization', config('flare.censor.headers'));
    }

    public function test_flare_reporting_defaults_off_without_credentials(): void
    {
        $this->assertFalse((bool) config('flare.report'));
    }

    public function test_flare_http_sender_posts_errors_to_ingress_when_configured(): void
    {
        Http::fake([
            'https://ingress.flareapp.io/*' => Http::response(['uuid' => 'test-uuid'], 200),
        ]);

        config(['flare.key' => 'test-flare-key']);

        $job = new SendFlarePayload(
            LaravelHttpSender::class,
            ['timeout' => 10],
            'https://ingress.flareapp.io/v1/errors',
            ['message' => 'Observability integration test'],
            FlareEntityType::Errors,
        );

        $job->handle(app('config'));

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'ingress.flareapp.io/v1/errors')
                && $request->hasHeader('x-api-token', 'test-flare-key');
        });
    }

    public function test_audit_logger_redaction_matches_flare_policy(): void
    {
        $redacted = AuditLogger::redactProperties([
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'notes' => 'Private note',
            'status' => 'approved',
        ]);

        $this->assertSame('[redacted]', $redacted['hours']);
        $this->assertSame('[redacted]', $redacted['notes']);
        $this->assertSame('approved', $redacted['status']);
    }
}
