@php
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="corp-timesheet-days-input-grid">
        @foreach ($days as $index => $day)
            <div @class([
                'corp-timesheet-day-input corp-timesheet-day-task',
                'is-weekend' => $index >= 5,
            ])>
                <label
                    for="{{ $getId() }}-{{ $index }}"
                    class="corp-timesheet-day-input-label"
                >
                    {{ $day }} task
                </label>
                <input
                    id="{{ $getId() }}-{{ $index }}"
                    type="text"
                    autocomplete="off"
                    placeholder="Activity / task"
                    {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}.{{ $index }}"
                    @disabled($isDisabled())
                    class="corp-timesheet-task-field"
                />
            </div>
        @endforeach
    </div>
</x-dynamic-component>
