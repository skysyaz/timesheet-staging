<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class WatchtowerReporter
{
    public function isEnabled(): bool
    {
        return filled(config('services.watchtower.url'))
            && filled(config('services.watchtower.token'));
    }

    /**
     * Mirror Flare noise filters — skip expected client errors.
     */
    public function shouldReport(Throwable $throwable): bool
    {
        if ($throwable instanceof NotFoundHttpException) {
            return false;
        }

        if ($throwable instanceof HttpExceptionInterface && $throwable->getStatusCode() < 500) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function report(Throwable $throwable, array $context = []): void
    {
        if (! $this->isEnabled() || ! $this->shouldReport($throwable)) {
            return;
        }

        $request = app()->runningInConsole() ? null : request();

        $payload = [
            'app_name' => config('services.watchtower.app_name'),
            'level' => 'error',
            'message' => $throwable->getMessage(),
            'trace' => $throwable->getTraceAsString(),
            'context' => array_filter(array_merge([
                'exception_class' => $throwable::class,
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'url' => $request?->fullUrl(),
            ], $context), fn ($value) => $value !== null),
        ];

        $url = rtrim((string) config('services.watchtower.url'), '/').'/api/errors';
        $token = (string) config('services.watchtower.token');

        $send = function () use ($url, $token, $payload): void {
            try {
                Http::withToken($token)
                    ->timeout(3)
                    ->post($url, $payload);
            } catch (Throwable) {
                // Never let a Watchtower outage break the app.
            }
        };

        // Sync in tests so Http::assertSent works without terminate();
        // defer on HTTP so the error response is not blocked by the 3s POST.
        if (app()->environment('testing')) {
            $send();

            return;
        }

        app()->terminating($send);
    }
}
