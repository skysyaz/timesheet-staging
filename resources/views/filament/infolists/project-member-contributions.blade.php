@php
    $project = $entry->getRecord();
    $rows = $project->memberContributions();
    $maxHours = collect($rows)->max('hours') ?: 0;
@endphp

@include('filament.infolists.partials.project-member-scroll-styles')

<div class="h-60">
    @if ($rows === [])
        <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
            </span>
            <p class="text-sm text-gray-500 dark:text-gray-400">No hours logged yet</p>
        </div>
    @else
        <div class="project-member-scroll h-full overflow-y-auto pr-1.5">
            <div class="flex flex-col gap-3">
                @foreach ($rows as $row)
                    @php
                        $hours = (float) $row['hours'];
                        $pct = $maxHours > 0 ? ($hours / $maxHours) * 100 : 0;
                        $roleLabel = filled($row['role']) && $row['role'] !== '—'
                            ? '('.mb_strtolower($row['role']).')'
                            : '';
                    @endphp
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="min-w-0 truncate text-sm text-gray-900 dark:text-white">
                                {{ $row['name'] }}
                                @if ($roleLabel !== '')
                                    <span class="text-gray-500 dark:text-gray-400">{{ $roleLabel }}</span>
                                @endif
                            </p>
                            <span class="shrink-0 text-sm font-bold tabular-nums text-gray-900 dark:text-white">
                                {{ number_format($hours, 1) }}h
                            </span>
                        </div>
                        <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-gray-200/80 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-500 dark:bg-primary-400" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
