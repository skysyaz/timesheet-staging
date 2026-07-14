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
        .corp-weekly-hours-attach-empty { color: rgb(148 163 184); font-size: 0.8rem; font-style: italic; }
        .corp-weekly-hours-attach-error { margin-top: 0.35rem; color: rgb(220 38 38); font-size: 0.75rem; }

        /* Enhanced upload zone */
        .corp-upload-zone { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.75rem; border: 2px dashed rgb(148 163 184 / 0.5); border-radius: 0.5rem; background: rgb(248 250 252 / 0.5); cursor: pointer; transition: border-color 0.2s, background 0.2s; }
        .corp-upload-zone:hover { border-color: rgb(37 99 235); background: rgb(219 234 254 / 0.3); }
        .dark .corp-upload-zone { border-color: rgb(71 85 105 / 0.5); background: rgb(30 41 59 / 0.4); }
        .dark .corp-upload-zone:hover { border-color: rgb(96 165 250); background: rgb(30 58 138 / 0.2); }
        .corp-upload-zone.has-file { border-style: solid; border-color: rgb(34 197 94); background: rgb(240 253 244 / 0.5); }
        .dark .corp-upload-zone.has-file { border-color: rgb(74 222 128); background: rgb(20 83 45 / 0.2); }
        .corp-upload-zone input[type="file"] { font-size: 0; width: 0; height: 0; opacity: 0; position: absolute; }
        .corp-upload-label { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; font-weight: 600; color: rgb(71 85 105); }
        .dark .corp-upload-label { color: rgb(148 163 184); }
        .corp-upload-zone:hover .corp-upload-label { color: rgb(37 99 235); }
        .dark .corp-upload-zone:hover .corp-upload-label { color: rgb(96 165 250); }
        .corp-upload-icon { flex-shrink: 0; }
        .corp-upload-file-name { font-size: 0.75rem; color: rgb(34 197 94); font-weight: 600; max-width: 12rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dark .corp-upload-file-name { color: rgb(74 222 128); }
        .corp-attach-btn { padding: 0.25rem 0.75rem; border-radius: 0.375rem; background: rgb(37 99 235); color: #fff; font-size: 0.75rem; font-weight: 600; border: none; cursor: pointer; transition: background 0.2s, transform 0.1s; display: inline-flex; align-items: center; gap: 0.25rem; }
        .corp-attach-btn:hover { background: rgb(29 78 216); }
        .corp-attach-btn:active { transform: scale(0.97); }
        .corp-attach-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .corp-attach-spinner { width: 0.875rem; height: 0.875rem; border: 2px solid rgb(255 255 255 / 0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
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
                                </div>

                                @if ($editable)
                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                        <label class="corp-upload-zone" wire:key="upload-{{ $rowIndex }}">
                                            <x-filament::icon icon="heroicon-m-arrow-up-tray" class="h-4 w-4 corp-upload-icon text-primary-500" />
                                            <input
                                                type="file"
                                                wire:model="rowUploads.{{ $rowIndex }}"
                                                class="sr-only"
                                            />
                                            <span class="corp-upload-label">
                                                <span wire:loading.remove wire:target="rowUploads.{{ $rowIndex }}">Choose file</span>
                                                <span wire:loading wire:target="rowUploads.{{ $rowIndex }}">Uploading…</span>
                                            </span>
                                            <span wire:loading.remove wire:target="uploadAttachment" class="corp-upload-file-name" x-data x-text="$wire.rowUploads[{{ $rowIndex }}]?.name || ''" style="display:none"></span>
                                        </label>
                                        <button
                                            type="button"
                                            wire:click="uploadAttachment({{ $rowIndex }})"
                                            wire:loading.attr="disabled"
                                            wire:target="rowUploads.{{ $rowIndex }},uploadAttachment"
                                            class="corp-attach-btn"
                                        >
                                            <span wire:loading.remove wire:target="rowUploads.{{ $rowIndex }},uploadAttachment">
                                                <x-filament::icon icon="heroicon-m-check" class="h-3.5 w-3.5 inline" />
                                                Attach
                                            </span>
                                            <span wire:loading wire:target="rowUploads.{{ $rowIndex }},uploadAttachment" class="inline-flex items-center gap-1">
                                                <span class="corp-attach-spinner"></span>
                                                Uploading…
                                            </span>
                                        </button>
                                    </div>
                                @endif
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
