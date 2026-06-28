@php
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $tasks = $entry->getRecord()->tasks ?? ['', '', '', '', '', '', ''];
@endphp

<div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($days as $i => $day)
        @php
            $task = trim((string) ($tasks[$i] ?? ''));
        @endphp
        @if (filled($task))
            <div class="rounded-lg border border-gray-200/80 bg-gray-50/50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $day }}</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $task }}</div>
            </div>
        @endif
    @endforeach
</div>
