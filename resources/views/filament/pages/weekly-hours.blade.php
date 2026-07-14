<x-filament-panels::page>
    <style>
        .corp-weekly-hours-ot-row td { border-top: 0; background: rgb(255 251 235 / 0.6); }
        .dark .corp-weekly-hours-ot-row td { background: rgb(69 26 3 / 0.2); }
        .corp-weekly-hours-ot-label { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: rgb(146 64 14); }
        .dark .corp-weekly-hours-ot-label { color: rgb(252 211 77); }
        .corp-weekly-hours-ot-row .corp-weekly-hours-hour-input { border-color: rgb(253 230 138 / 0.8); background: rgb(255 251 235 / 0.5); }
        .corp-weekly-hours-total-row-ot td { background: rgb(255 251 235 / 0.8); font-weight: 600; }
        .dark .corp-weekly-hours-total-row-ot td { background: rgb(69 26 3 / 0.3); }
        .corp-weekly-hours-attach-row td { border-top: 0; background: rgb(248 250 252 / 0.7); }
        .dark .corp-weekly-hours-attach-row td { background: rgb(30 41 59 / 0.3); }
        .corp-weekly-hours-attach-label { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: rgb(100 116 139); }
        .dark .corp-weekly-hours-attach-label { color: rgb(148 163 184); }
        .corp-weekly-hours-attachments { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
        .corp-weekly-hours-attach-chip { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.5rem; border-radius: 9999px; background: rgb(226 232 240 / 0.7); font-size: 0.8rem; }
        .dark .corp-weekly-hours-attach-chip { background: rgb(51 65 85 / 0.6); }
        .corp-weekly-hours-attach-chip a { color: rgb(37 99 235); text-decoration: none; font-weight: 500; }
        .corp-weekly-hours-attach-chip a:hover { text-decoration: underline; }
        .dark .corp-weekly-hours-attach-chip a { color: rgb(96 165 250); }
        .corp-weekly-hours-attach-size { color: rgb(100 116 139); font-size: 0.7rem; }
        .corp-weekly-hours-attach-remove { display: inline-flex; color: rgb(148 163 184); }
        .corp-weekly-hours-attach-remove:hover { color: rgb(220 38 38); }
        .corp-weekly-hours-attach-empty, .corp-weekly-hours-attach-hint { color: rgb(148 163 184); font-size: 0.8rem; font-style: italic; }
        .corp-weekly-hours-attach-upload { display: inline-flex; align-items: center; gap: 0.5rem; }
        .corp-weekly-hours-attach-input { font-size: 0.75rem; max-width: 15rem; }
        .corp-weekly-hours-attach-btn { padding: 0.2rem 0.6rem; border-radius: 0.375rem; background: rgb(37 99 235); color: #fff; font-size: 0.75rem; font-weight: 600; }
        .corp-weekly-hours-attach-btn:disabled { opacity: 0.5; }
        .corp-weekly-hours-attach-error { margin-top: 0.35rem; color: rgb(220 38 38); font-size: 0.75rem; }
    </style>
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
                                <td class="corp-weekly-hours-actions" rowspan="3">
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
                        @php
                            $attachments = $row['attachments'] ?? [];
                        @endphp
                        <tr
                            wire:key="weekly-hours-attach-row-{{ $row['id'] ?? 'new-' . $rowIndex }}"
                            class="corp-weekly-hours-attach-row"
                        >
                            <td class="corp-weekly-hours-attach-label">Files</td>
                            <td colspan="9">
                                <div class="corp-weekly-hours-attachments">
                                    @forelse ($attachments as $attachment)
                                        <span class="corp-weekly-hours-attach-chip">
                                            <x-filament::icon icon="heroicon-m-paper-clip" class="h-3.5 w-3.5" />
                                            <a href="{{ $this->attachmentDownloadUrl($attachment['id']) }}" target="_blank" rel="noopener">
                                                {{ $attachment['name'] }}
                                            </a>
                                            <span class="corp-weekly-hours-attach-size">{{ $attachment['size'] }}</span>
                                            @if ($editable)
                                                <button
                                                    type="button"
                                                    wire:click="removeAttachment({{ $attachment['id'] }})"
                                                    wire:confirm="Remove this attachment?"
                                                    class="corp-weekly-hours-attach-remove"
                                                    aria-label="Remove attachment"
                                                >
                                                    <x-filament::icon icon="heroicon-m-x-mark" class="h-3 w-3" />
                                                </button>
                                            @endif
                                        </span>
                                    @empty
                                        <span class="corp-weekly-hours-attach-empty">No files attached</span>
                                    @endforelse

                                    @if ($editable)
                                        <span class="corp-weekly-hours-attach-upload">
                                            <input
                                                type="file"
                                                wire:model="rowUploads.{{ $rowIndex }}"
                                                class="corp-weekly-hours-attach-input"
                                            />
                                            <span wire:loading wire:target="rowUploads.{{ $rowIndex }}" class="corp-weekly-hours-attach-hint">Uploading…</span>
                                            <button
                                                type="button"
                                                wire:click="uploadAttachment({{ $rowIndex }})"
                                                wire:loading.attr="disabled"
                                                wire:target="rowUploads.{{ $rowIndex }},uploadAttachment"
                                                class="corp-weekly-hours-attach-btn"
                                            >
                                                Attach
                                            </button>
                                        </span>
                                    @endif
                                </div>
                                @error('rowUploads.' . $rowIndex)
                                    <div class="corp-weekly-hours-attach-error">{{ $message }}</div>
                                @enderror
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
