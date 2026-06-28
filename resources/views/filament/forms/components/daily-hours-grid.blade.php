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
                'corp-timesheet-day-input',
                'is-weekend' => $index >= 5,
            ])>
                <label
                    for="{{ $getId() }}-{{ $index }}"
                    class="corp-timesheet-day-input-label"
                >
                    {{ $day }}
                </label>
                <input
                    id="{{ $getId() }}-{{ $index }}"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    placeholder="0"
                    {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}.{{ $index }}"
                    @disabled($isDisabled())
                    class="corp-timesheet-hour-field"
                />
                <span class="corp-timesheet-day-input-suffix">hours</span>
            </div>
        @endforeach
    </div>
</x-dynamic-component>
