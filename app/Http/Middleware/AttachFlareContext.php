<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\LaravelFlare\Facades\Flare;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AttachFlareContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (filled(config('flare.key')) && config('flare.report')) {
            try {
                Flare::context([
                    'tenant' => config('observability.flare.tenant'),
                    'application' => config('observability.flare.application'),
                    'environment' => config('app.env'),
                    'request_id' => $request->header('X-Request-Id') ?? $request->fingerprint(),
                ]);

                $user = $request->user();

                if ($user !== null) {
                    Flare::context([
                        'user_id' => $user->id,
                        'user_role' => $user->role,
                    ]);
                }
            } catch (Throwable) {
                // Flare may not be ready during early requests.
            }
        }

        return $next($request);
    }
}
