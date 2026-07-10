<?php

namespace App\Support;

use App\Models\SiteTrafficDaily;
use Filament\Facades\Filament;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteTrafficRecorder
{
    public function record(Request $request): void
    {
        if (! config('observability.traffic.enabled')) {
            return;
        }

        if (! $this->shouldRecord($request)) {
            return;
        }

        $date = now()->toDateString();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $sessionKey = $sessionId
            ? 'site_traffic:session:' . $date . ':' . $sessionId
            : null;

        $isNewSession = $sessionKey === null
            || Cache::add($sessionKey, true, now()->endOfDay());

        $row = $this->dayRow($date);

        $row->increment('page_views');

        if ($isNewSession) {
            $row->increment('unique_sessions');
        }
    }

    public function totalPageViewsForDays(int $days): int
    {
        $from = now()->subDays(max($days - 1, 0))->toDateString();

        return (int) SiteTrafficDaily::query()
            ->whereDate('date', '>=', $from)
            ->sum('page_views');
    }

    public function totalUniqueSessionsForDays(int $days): int
    {
        $from = now()->subDays(max($days - 1, 0))->toDateString();

        return (int) SiteTrafficDaily::query()
            ->whereDate('date', '>=', $from)
            ->sum('unique_sessions');
    }

    public function todayPageViews(): int
    {
        return (int) SiteTrafficDaily::query()
            ->whereDate('date', now()->toDateString())
            ->value('page_views');
    }

    protected function dayRow(string $date): SiteTrafficDaily
    {
        $existing = SiteTrafficDaily::query()->whereDate('date', $date)->first();

        if ($existing) {
            return $existing;
        }

        try {
            return SiteTrafficDaily::query()->create([
                'date' => $date,
                'page_views' => 0,
                'unique_sessions' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Concurrent request won the insert; date cast/storage can differ
            // across drivers, so look up with whereDate rather than exact match.
            return SiteTrafficDaily::query()->whereDate('date', $date)->firstOrFail();
        }
    }

    protected function shouldRecord(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->is('livewire/*')) {
            return false;
        }

        if ($request->is(
            'up',
            'uptime/*',
            '.well-known/*',
            'build/*',
            'css/*',
            'js/*',
            'fonts/*',
            'storage/*',
            'favicon.ico',
            'branding/*',
            'site.webmanifest',
            'logo.webp',
        )) {
            return false;
        }

        $panelPath = trim(Filament::getDefaultPanel()->getPath(), '/');

        if ($panelPath === '') {
            return true;
        }

        return $request->is($panelPath, $panelPath . '/*');
    }
}
