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

        if ($request->is('admin/*') || $request->is('admin')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

            if ($this->shouldPreventAdminPageCache($request, $response)) {
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
                $response->headers->set('Pragma', 'no-cache');
            }
        } else {
            $response->headers->set('X-Frame-Options', 'DENY');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
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

    private function shouldPreventAdminPageCache(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html');
    }

    private function contentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
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
