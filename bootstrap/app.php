<?php

use App\Http\Middleware\AttachFlareContext;
use App\Http\Middleware\RestrictHealthCheck;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
        $middleware->redirectGuestsTo('/admin/login');
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
    })->create();
