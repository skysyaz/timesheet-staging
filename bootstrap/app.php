<?php

use App\Http\Middleware\AttachFlareContext;
use App\Http\Middleware\RestrictHealthCheck;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelFlare\Facades\Flare;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function (): void {
            Route::middleware([RestrictHealthCheck::class])
                ->get('/up', function (Request $request) {
                    if ($request->wantsJson()) {
                        return response()->json(['status' => 'up']);
                    }

                    return response('OK', 200);
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [
            AttachFlareContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        if (filled(config('flare.key')) && config('flare.report')) {
            Flare::handles($exceptions);

            Flare::filterExceptionsUsing(function (\Throwable $throwable): bool {
                if ($throwable instanceof NotFoundHttpException) {
                    return false;
                }

                if ($throwable instanceof HttpExceptionInterface && $throwable->getStatusCode() < 500) {
                    return false;
                }

                return true;
            });
        }

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->report(function (\Throwable $e): void {
            $watchtowerUrl = config('services.watchtower.url');
            $watchtowerToken = config('services.watchtower.token');

            if (! filled($watchtowerUrl) || ! filled($watchtowerToken)) {
                return;
            }

            try {
                $request = app()->runningInConsole() ? null : request();

                Http::withToken($watchtowerToken)
                    ->timeout(3)
                    ->post(rtrim($watchtowerUrl, '/').'/api/errors', [
                        'app_name' => config('services.watchtower.app_name'),
                        'level' => 'error',
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'context' => array_filter([
                            'exception_class' => $e::class,
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'url' => $request?->fullUrl(),
                        ], fn ($value) => $value !== null),
                    ]);
            } catch (\Throwable) {
                // Never let a Watchtower outage break the app.
            }
        });
    })->create();
