@php
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $hours = $entry->getRecord()->hours ?? [0, 0, 0, 0, 0, 0, 0];
    $total = array_sum($hours);
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
        @foreach ($days as $i => $day)
            @php
                $h = (float) ($hours[$i] ?? 0);
                $isWeekend = $i >= 5;
                $isOvertime = $h > 8;
            @endphp
            <div @class([
                'corp-day-cell',
                'is-weekend' => $isWeekend,
                'is-overtime' => $isOvertime,
            ])>
                <span class="corp-day-label">{{ $day }}</span>
                <span class="corp-day-hours">{{ $h == (int) $h ? (int) $h : number_format($h, 1) }}</span>
                <span class="text-xs text-gray-400">hours</span>
            </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between rounded-xl border border-gray-200/80 bg-gray-50/50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800/50">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Weekly Total</span>
        <span class="text-lg font-bold tabular-nums text-gray-900 dark:text-white">
            {{ number_format($total, 1) }} hours
        </span>
    </div>
</div>
