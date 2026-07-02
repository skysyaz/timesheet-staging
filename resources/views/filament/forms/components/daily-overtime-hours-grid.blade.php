@php
    use Carbon\Carbon;

    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $statePath = $getStatePath();
    $weekStart = filled($get('week_start')) ? Carbon::parse($get('week_start')) : null;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="corp-timesheet-ot-section">
        <p class="corp-timesheet-ot-heading">Overtime hours</p>

        <div class="corp-timesheet-ot-strip">
            <div class="corp-timesheet-ot-strip-spacer" aria-hidden="true"></div>

            <div class="corp-timesheet-days-input-grid">
                @foreach ($days as $index => $day)
                    <div @class([
                        'corp-timesheet-day-input',
                        'is-weekend' => $index >= 5,
                        'is-overtime' => true,
                    ])>
                        <label
                            for="{{ $getId() }}-{{ $index }}"
                            class="corp-timesheet-day-input-label"
                        >
                            <span class="corp-timesheet-day-input-day">{{ $day }} OT</span>
                            @if ($weekStart)
                                <span class="corp-timesheet-day-input-date">
                                    {{ $weekStart->copy()->addDays($index)->format('d/m') }}
                                </span>
                            @endif
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

            <div class="corp-timesheet-ot-strip-spacer" aria-hidden="true"></div>
        </div>
    </div>
</x-dynamic-component>
