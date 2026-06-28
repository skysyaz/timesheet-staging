<?php

namespace App\Http\Middleware;

use App\Support\SiteTrafficRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordSiteTraffic
{
    public function __construct(
        protected SiteTrafficRecorder $recorder,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() < 400) {
            $this->recorder->record($request);
        }

        return $response;
    }
}
