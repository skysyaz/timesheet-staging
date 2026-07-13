@php
    $attachments = $entry->getRecord()->attachments;
@endphp

<div class="space-y-2">
    @forelse ($attachments as $attachment)
        <a
            href="{{ route('timesheet-attachments.download', ['attachment' => $attachment->id]) }}"
            target="_blank"
            rel="noopener"
            class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
        >
            <span class="flex min-w-0 items-center gap-2">
                <x-filament::icon icon="heroicon-o-paper-clip" class="h-4 w-4 shrink-0 text-gray-400" />
                <span class="truncate font-medium text-primary-600 dark:text-primary-400">{{ $attachment->original_name }}</span>
            </span>
            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $attachment->humanSize() }}</span>
        </a>
    @empty
        <p class="text-sm italic text-gray-400">No attachments.</p>
    @endforelse
</div>
