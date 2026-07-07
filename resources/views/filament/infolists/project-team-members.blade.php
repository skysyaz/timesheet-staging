@php
    $project = $entry->getRecord();
    $members = $project->members->sortBy('name');
@endphp

@include('filament.infolists.partials.project-member-scroll-styles')

<div class="h-60">
    @if ($members->isEmpty())
        <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </span>
            <p class="text-sm text-gray-500 dark:text-gray-400">No members assigned yet</p>
        </div>
    @else
        <div class="project-member-scroll h-full overflow-y-auto pr-1.5">
            <div class="flex flex-col gap-2">
                @foreach ($members as $member)
                    @php
                        $initial = mb_strtoupper(mb_substr($member->name ?? '?', 0, 1));
                        $role = $member->pivot->assigned_role ?? '—';
                    @endphp
                    <div class="flex items-center gap-3 rounded-lg border border-gray-200/80 px-3 py-2.5 dark:border-gray-700/80">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600/20 text-sm font-semibold text-primary-600 dark:bg-primary-500/20 dark:text-primary-400">
                            {{ $initial }}
                        </span>
                        <p class="min-w-0 flex-1 truncate text-sm font-medium text-gray-900 dark:text-white">
                            {{ $member->name }}
                        </p>
                        <span class="shrink-0 text-sm text-gray-500 dark:text-gray-400">
                            {{ $role }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
