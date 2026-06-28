<div class="space-y-3 text-sm">
    <div>
        <span class="font-semibold text-gray-700 dark:text-gray-300">Description</span>
        <p>{{ $record->description }}</p>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">When</span>
            <p>{{ $record->created_at?->format('M j, Y H:i:s') }}</p>
        </div>
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">User</span>
            <p>{{ $record->causer?->name ?? 'System' }}</p>
        </div>
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">Log</span>
            <p>{{ $record->log_name }}</p>
        </div>
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">Event</span>
            <p>{{ $record->event ?? '—' }}</p>
        </div>
    </div>
    @if ($record->properties)
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">Properties</span>
            <pre class="mt-1 overflow-x-auto rounded-lg bg-gray-50 p-3 text-xs dark:bg-gray-900">{{ json_encode($record->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>
