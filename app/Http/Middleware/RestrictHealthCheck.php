<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictHealthCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAllowed($request)) {
            return $next($request);
        }

        abort(404);
    }

    private function isAllowed(Request $request): bool
    {
        if (app()->environment('local', 'testing')) {
            return true;
        }

        // Do not auto-allow 127.0.0.1: behind a reverse proxy every request can
        // look local. Rely on the configured allowlist for non-local envs.
        $allowed = array_filter(array_map(
            trim(...),
            explode(',', (string) config('security.health_check_allowed_ips', '')),
        ));

        return in_array($request->ip(), $allowed, true);
    }
}
