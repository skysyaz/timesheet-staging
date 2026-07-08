<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');

        if ($this->shouldPreventAuthenticatedPageCache($request, $response)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // ponytail: emit HSTS whenever the origin is HTTPS, not just when the
        // current leg is TLS — behind a TLS-terminating proxy the internal leg
        // is plain HTTP and $request->secure() is false, which would silently
        // drop HSTS. Trusting APP_URL keeps it on for HTTPS deployments.
        if ($request->secure() || str_starts_with((string) config('app.url'), 'https')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload',
            );
        }

        $csp = $this->contentSecurityPolicy();

        if (config('security.csp_report_only')) {
            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
        }

        if (config('security.csp_enforce')) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        if ($coop = config('security.coop')) {
            $response->headers->set('Cross-Origin-Opener-Policy', $coop);
        }

        if ($corp = config('security.corp')) {
            $response->headers->set('Cross-Origin-Resource-Policy', $corp);
        }

        return $response;
    }

    private function shouldPreventAuthenticatedPageCache(Request $request, Response $response): bool
    {
        // Guard on authentication, not a URL prefix — the Filament panel is
        // mounted at the root (->path('')), so an admin/* check never matched
        // an actual authenticated page and left them cacheable.
        if (! $request->isMethod('GET')) {
            return false;
        }

        if (! auth()->check()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html');
    }

    private function contentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            // 'unsafe-eval' and the jsdelivr CDN were unused (Livewire v3 /
            // Filament v5 / Alpine v3 don't need eval) and let any HTML
            // injection load arbitrary scripts. 'unsafe-inline' stays until a
            // per-request nonce is wired through Filament's render hooks.
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
