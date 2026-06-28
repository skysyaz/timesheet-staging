@php
    use Carbon\Carbon;

    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $hoursPath = $getStatePath();
    $tasksPath = str_ends_with($hoursPath, '.hours')
        ? substr($hoursPath, 0, -strlen('.hours')) . '.tasks'
        : 'tasks';

    $weekStart = filled($get('week_start')) ? Carbon::parse($get('week_start')) : null;
    $workDate = filled($get('work_date')) ? Carbon::parse($get('work_date')) : null;
    $hours = $get('hours') ?? [0, 0, 0, 0, 0, 0, 0];
    $tasks = $get('tasks') ?? ['', '', '', '', '', '', ''];

    $initialDay = 0;

    if ($workDate && $weekStart) {
        $initialDay = max(0, min(6, $workDate->diffInDays($weekStart)));
    } elseif ($weekStart) {
        $today = now()->startOfDay();

        if ($today->between($weekStart->copy()->startOfDay(), $weekStart->copy()->addDays(6)->endOfDay())) {
            $initialDay = max(0, min(6, $today->diffInDays($weekStart)));
        }
    }

    $weekDates = $weekStart
        ? collect(range(0, 6))->map(fn (int $index): array => [
            'label' => $days[$index],
            'date' => $weekStart->copy()->addDays($index)->format('d/m/Y'),
            'longDate' => $weekStart->copy()->addDays($index)->format('l, j M Y'),
            'hours' => (float) ($hours[$index] ?? 0),
            'task' => trim((string) ($tasks[$index] ?? '')),
            'isWeekend' => $weekStart->copy()->addDays($index)->isWeekend(),
        ])->values()->all()
        : [];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            activeDay: @js($initialDay),
            weekDates: @js($weekDates),
            focusDay(index) {
                const day = Number(index);

                if (Number.isNaN(day) || day < 0 || day > 6) {
                    return;
                }

                this.activeDay = day;

                this.$nextTick(() => {
                    const hours = this.$refs[`hours${day}`];

                    if (hours) {
                        hours.focus();
                        hours.select?.();
                    }
                });
            },
            previousDay() {
                if (this.activeDay > 0) {
                    this.focusDay(this.activeDay - 1);
                }
            },
            nextDay() {
                if (this.activeDay < 6) {
                    this.focusDay(this.activeDay + 1);
                }
            },
            weekTotal() {
                return this.weekDates
                    .reduce((total, day) => total + (Number(day.hours) || 0), 0)
                    .toFixed(1)
                    .replace(/\.0$/, '');
            },
            syncHours(index, value) {
                this.weekDates[index].hours = Number(value) || 0;
            },
            syncTask(index, value) {
                this.weekDates[index].task = value ?? '';
            },
        }"
        x-init="$nextTick(() => focusDay(activeDay))"
        x-on:timesheet-date-chosen.window="focusDay($event.detail.dayIndex)"
        class="corp-ts-planner"
    >
        <div class="corp-ts-planner-strip">
            <button
                type="button"
                class="corp-ts-planner-nav"
                x-on:click="previousDay()"
                x-bind:disabled="activeDay === 0"
                aria-label="Previous day"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" />
                </svg>
            </button>

            <div class="corp-ts-planner-days">
                <template x-for="(day, index) in weekDates" :key="index">
                    <button
                        type="button"
                        class="corp-ts-planner-day"
                        x-bind:class="{
                            'is-active': activeDay === index,
                            'is-weekend': day.isWeekend,
                            'has-entry': day.hours > 0 || day.task.length > 0,
                        }"
                        x-on:click="focusDay(index)"
                    >
                        <span class="corp-ts-planner-day-label" x-text="day.label"></span>
                        <span class="corp-ts-planner-day-number" x-text="day.date.split('/')[0]"></span>
                        <span class="corp-ts-planner-day-hours" x-show="day.hours > 0" x-text="`${day.hours}h`"></span>
                    </button>
                </template>
            </div>

            <button
                type="button"
                class="corp-ts-planner-nav"
                x-on:click="nextDay()"
                x-bind:disabled="activeDay === 6"
                aria-label="Next day"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="corp-ts-planner-panel">
            <div class="corp-ts-planner-panel-header">
                <div>
                    <p class="corp-ts-planner-panel-kicker">Selected day</p>
                    <h3 class="corp-ts-planner-panel-title" x-text="weekDates[activeDay]?.longDate ?? ''"></h3>
                </div>
                <div class="corp-ts-planner-panel-total">
                    <span class="corp-ts-planner-panel-total-label">Week total</span>
                    <span class="corp-ts-planner-panel-total-value" x-text="`${weekTotal()}h`"></span>
                </div>
            </div>

            @foreach ($days as $index => $day)
                <div
                    x-show="activeDay === {{ $index }}"
                    x-cloak
                    class="corp-ts-planner-entry"
                >
                    <div class="corp-ts-planner-hours-wrap">
                        <label for="{{ $getId() }}-hours-{{ $index }}" class="corp-ts-planner-field-label">Hours worked</label>
                        <div class="corp-ts-planner-hours-input">
                            <input
                                id="{{ $getId() }}-hours-{{ $index }}"
                                type="text"
                                inputmode="decimal"
                                autocomplete="off"
                                placeholder="0"
                                {{ $applyStateBindingModifiers('wire:model') }}="{{ $hoursPath }}.{{ $index }}"
                                x-ref="hours{{ $index }}"
                                x-on:input="syncHours({{ $index }}, $event.target.value)"
                                x-on:keydown.arrow-right.prevent="nextDay()"
                                @disabled($isDisabled())
                                class="corp-ts-planner-hours-field"
                            />
                            <span>hours</span>
                        </div>
                    </div>

                    <div class="corp-ts-planner-task-wrap">
                        <label for="{{ $getId() }}-task-{{ $index }}" class="corp-ts-planner-field-label">Activity / task</label>
                        <textarea
                            id="{{ $getId() }}-task-{{ $index }}"
                            rows="3"
                            placeholder="What did you work on this day?"
                            {{ $applyStateBindingModifiers('wire:model') }}="{{ $tasksPath }}.{{ $index }}"
                            x-ref="task{{ $index }}"
                            x-on:input="syncTask({{ $index }}, $event.target.value)"
                            @disabled($isDisabled())
                            class="corp-ts-planner-task-field"
                        ></textarea>
                    </div>
                </div>
            @endforeach

            <div class="corp-ts-planner-actions">
                <button type="button" class="corp-ts-planner-action" x-on:click="previousDay()" x-bind:disabled="activeDay === 0">
                    Previous day
                </button>
                <button type="button" class="corp-ts-planner-action is-primary" x-on:click="nextDay()" x-bind:disabled="activeDay === 6">
                    Next day
                </button>
            </div>
        </div>
    </div>
</x-dynamic-component>
