<x-filament-panels::page>
    <div class="corp-weekly-hours">
        <div class="corp-weekly-hours-toolbar">
            <div class="corp-weekly-hours-week-nav">
                <button type="button" wire:click="previousWeek" class="corp-weekly-hours-nav-btn" aria-label="Previous week">
                    <x-filament::icon icon="heroicon-m-chevron-left" class="h-4 w-4" />
                </button>

                <div class="corp-weekly-hours-week-label">
                    {{ $this->getWeekLabel() }}
                </div>

                <button type="button" wire:click="nextWeek" class="corp-weekly-hours-nav-btn" aria-label="Next week">
                    <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4" />
                </button>
            </div>

            @if ($this->getUserOptions() !== [])
                <div class="corp-weekly-hours-user-select">
                    <select wire:model.live="selectedUserId" class="corp-weekly-hours-select">
                        @foreach ($this->getUserOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="corp-weekly-hours-user-badge">
                    <span class="corp-weekly-hours-user-avatar">{{ str($this->selectedUser()->name)->substr(0, 2)->upper() }}</span>
                    <span>{{ $this->selectedUser()->name }}</span>
                </div>
            @endif
        </div>

        <div class="corp-weekly-hours-table-wrap">
            <table class="corp-weekly-hours-table">
                <thead>
                    <tr>
                        <th class="corp-weekly-hours-th-project">Project</th>
                        <th class="corp-weekly-hours-th-activity">Activity</th>
                        @foreach ($this->getDayHeaders() as $index => $label)
                            <th @class([
                                'corp-weekly-hours-th-day',
                                'is-weekend' => $index >= 5,
                            ])>{{ strtoupper($label) }}</th>
                        @endforeach
                        <th class="corp-weekly-hours-th-duration">Duration</th>
                        @if ($this->canEditSheet())
                            <th class="corp-weekly-hours-th-actions"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $rowIndex => $row)
                        @php
                            $editable = ($row['editable'] ?? false) && $this->canEditSheet();
                        @endphp
                        <tr wire:key="weekly-hours-row-{{ $row['id'] ?? 'new-' . $rowIndex }}">
                            <td>
                                @if ($editable)
                                    <select wire:model="rows.{{ $rowIndex }}.project_id" class="corp-weekly-hours-select">
                                        <option value="">Select project</option>
                                        @foreach ($this->getProjectOptions() as $projectId => $projectName)
                                            <option value="{{ $projectId }}">{{ $projectName }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="corp-weekly-hours-readonly">
                                        {{ $row['project_name'] ?: '—' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <input
                                        type="text"
                                        wire:model="rows.{{ $rowIndex }}.activity"
                                        placeholder="What you worked on"
                                        class="corp-weekly-hours-input"
                                    />
                                @else
                                    <span class="corp-weekly-hours-readonly">{{ $row['activity'] ?: '—' }}</span>
                                @endif
                            </td>
                            @foreach (range(0, 6) as $dayIndex)
                                <td @class([
                                    'corp-weekly-hours-td-day',
                                    'is-weekend' => $dayIndex >= 5,
                                ])>
                                    @if ($editable)
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            wire:model.live="rows.{{ $rowIndex }}.hours.{{ $dayIndex }}"
                                            placeholder="0:00"
                                            class="corp-weekly-hours-hour-input"
                                        />
                                    @else
                                        <span class="corp-weekly-hours-readonly">{{ $this->formatHours($row['hours'][$dayIndex] ?? 0) }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="corp-weekly-hours-duration">
                                {{ $this->rowDuration($row) }}
                            </td>
                            @if ($this->canEditSheet())
                                <td class="corp-weekly-hours-actions" rowspan="2">
                                    @if ($editable)
                                        <button
                                            type="button"
                                            wire:click="removeRow({{ $rowIndex }})"
                                            class="corp-weekly-hours-remove"
                                            aria-label="Remove row"
                                        >
                                            <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                        <tr
                            wire:key="weekly-hours-ot-row-{{ $row['id'] ?? 'new-' . $rowIndex }}"
                            class="corp-weekly-hours-ot-row"
                        >
                            <td colspan="2" class="corp-weekly-hours-ot-label">Overtime</td>
                            @foreach (range(0, 6) as $dayIndex)
                                <td @class([
                                    'corp-weekly-hours-td-day',
                                    'is-weekend' => $dayIndex >= 5,
                                ])>
                                    @if ($editable)
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            wire:model.live="rows.{{ $rowIndex }}.overtime_hours.{{ $dayIndex }}"
                                            placeholder="0:00"
                                            class="corp-weekly-hours-hour-input"
                                        />
                                    @else
                                        <span class="corp-weekly-hours-readonly">{{ $this->formatHours($row['overtime_hours'][$dayIndex] ?? 0) }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="corp-weekly-hours-duration">
                                {{ $this->rowOvertimeDuration($row) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="corp-weekly-hours-total-label">Regular total</td>
                        @foreach ($this->columnTotals() as $index => $total)
                            <td @class([
                                'corp-weekly-hours-total-cell',
                                'corp-weekly-hours-td-day',
                                'is-weekend' => $index >= 5,
                            ])>{{ $total }}</td>
                        @endforeach
                        <td class="corp-weekly-hours-total-cell corp-weekly-hours-duration">{{ $this->grandTotal() }}</td>
                        @if ($this->canEditSheet())
                            <td></td>
                        @endif
                    </tr>
                    <tr class="corp-weekly-hours-total-row-ot">
                        <td colspan="2" class="corp-weekly-hours-total-label">Overtime total</td>
                        @foreach ($this->columnOvertimeTotals() as $index => $total)
                            <td @class([
                                'corp-weekly-hours-total-cell',
                                'corp-weekly-hours-td-day',
                                'is-weekend' => $index >= 5,
                            ])>{{ $total }}</td>
                        @endforeach
                        <td class="corp-weekly-hours-total-cell corp-weekly-hours-duration">{{ $this->grandOvertimeTotal() }}</td>
                        @if ($this->canEditSheet())
                            <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($this->canEditSheet())
            <div class="corp-weekly-hours-footer">
                <x-filament::button wire:click="save" color="primary">
                    Save
                </x-filament::button>

                <x-filament::button wire:click="addRow" color="success" outlined>
                    + Add
                </x-filament::button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
