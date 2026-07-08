<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class UptimeHeartbeatController extends Controller
{
    public function scheduler(Request $request): Response
    {
        if (! $this->tokenIsValid($request)) {
            return response('Forbidden', 403);
        }

        if (! config('observability.uptime.enabled')) {
            return response('Uptime monitoring disabled', 503);
        }

        if ($this->cacheIsFresh(
            config('observability.uptime.cache_key_scheduler'),
            config('observability.uptime.scheduler_stale_minutes'),
        )) {
            return response('OK', 200);
        }

        return response('Scheduler heartbeat stale', 503);
    }

    public function queue(Request $request): Response
    {
        if (! $this->tokenIsValid($request)) {
            return response('Forbidden', 403);
        }

        if (! config('observability.uptime.enabled')) {
            return response('Uptime monitoring disabled', 503);
        }

        if ($this->cacheIsFresh(
            config('observability.uptime.cache_key_queue'),
            config('observability.uptime.queue_stale_minutes'),
        )) {
            return response('OK', 200);
        }

        return response('Queue heartbeat stale', 503);
    }

    protected function tokenIsValid(Request $request): bool
    {
        $configured = (string) config('observability.uptime.heartbeat_token');

        if ($configured === '') {
            return false;
        }

        // Read the token from a header, not the query string — query strings
        // leak into access logs, Referer, and history. External uptime
        // monitors must send `X-Uptime-Token: <token>` instead of ?token=.
        return hash_equals($configured, (string) $request->header('X-Uptime-Token', ''));
    }

    protected function cacheIsFresh(string $key, int $staleMinutes): bool
    {
        $lastSuccess = Cache::get($key);

        if (! is_int($lastSuccess)) {
            return false;
        }

        return $lastSuccess >= now()->subMinutes($staleMinutes)->timestamp;
    }
}
