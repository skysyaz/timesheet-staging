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
        <style>
            .corp-timesheet-ot-section { margin-top: 0.25rem; }
            .corp-timesheet-ot-heading { margin-bottom: 0.75rem; font-size: 0.875rem; font-weight: 600; color: rgb(55 65 81); }
            .corp-timesheet-ot-strip { display: grid; grid-template-columns: auto 1fr auto; gap: 0.75rem; align-items: stretch; }
            .corp-timesheet-ot-strip-spacer { width: 2.25rem; flex-shrink: 0; }
            .corp-timesheet-ot-strip .corp-timesheet-days-input-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.5rem; }
            .corp-timesheet-day-input-label { display: flex; flex-direction: column; align-items: center; gap: 0.1rem; }
            .corp-timesheet-day-input-date { font-size: 0.8125rem; font-weight: 700; line-height: 1; letter-spacing: normal; text-transform: none; color: rgb(31 41 55); }
            @media (max-width: 768px) {
                .corp-timesheet-ot-strip .corp-timesheet-days-input-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            }
        </style>
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
