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

        /* Inline badge bar */
        .corp-files-bar { display: flex; align-items: center; gap: 0.4rem; flex-wrap: nowrap; }
        .corp-files-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.15rem 0.55rem; border-radius: 0.375rem; background: rgb(226 232 240 / 0.6); font-size: 0.75rem; font-weight: 600; color: rgb(71 85 105); cursor: pointer; transition: background 0.15s; white-space: nowrap; user-select: none; }
        .corp-files-badge:hover { background: rgb(203 213 225 / 0.7); }
        .dark .corp-files-badge { background: rgb(51 65 85 / 0.5); color: rgb(148 163 184); }
        .dark .corp-files-badge:hover { background: rgb(71 85 105 / 0.6); }
        .corp-files-badge svg { transition: transform 0.2s; }
        .corp-files-badge.is-open svg { transform: rotate(90deg); }
        .corp-files-empty { font-size: 0.75rem; color: rgb(148 163 184); font-style: italic; }
        .dark .corp-files-empty { color: rgb(100 116 139); }

        /* Compact icon button for upload */
        .corp-attach-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 0.375rem; border: 1px solid rgb(203 213 225); background: rgb(248 250 252); cursor: pointer; transition: all 0.15s; position: relative; flex-shrink: 0; }
        .corp-attach-icon-btn:hover { border-color: rgb(37 99 235); background: rgb(219 234 254 / 0.5); }
        .corp-attach-icon-btn svg { color: rgb(100 116 139); transition: color 0.15s; }
        .corp-attach-icon-btn:hover svg { color: rgb(37 99 235); }
        .dark .corp-attach-icon-btn { border-color: rgb(51 65 85); background: rgb(30 41 59 / 0.5); }
        .dark .corp-attach-icon-btn:hover { border-color: rgb(96 165 250); background: rgb(30 58 138 / 0.2); }
        .dark .corp-attach-icon-btn svg { color: rgb(148 163 184); }
        .dark .corp-attach-icon-btn:hover svg { color: rgb(96 165 250); }
        .corp-attach-icon-btn input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .corp-attach-icon-btn.is-loading { border-color: rgb(37 99 235); }
        .corp-attach-spinner { width: 0.875rem; height: 0.875rem; border: 2px solid rgb(255 255 255 / 0.3); border-top-color: rgb(37 99 235); border-radius: 50%; animation: corp-spin 0.6s linear infinite; }
        @keyframes corp-spin { to { transform: rotate(360deg); } }

        /* Expanded file list */
        .corp-files-expanded { margin-top: 0.35rem; display: flex; flex-direction: column; gap: 0.2rem; }
        .corp-file-item { display: flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; background: var(--surface-1, rgb(241 245 249)); font-size: 0.78rem; }
        .dark .corp-file-item { background: var(--surface-1-dark, rgb(30 41 59 / 0.5)); }
        .corp-file-item a { color: rgb(37 99 235); font-weight: 600; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .corp-file-item a:hover { text-decoration: underline; }
        .dark .corp-file-item a { color: rgb(96 165 250); }
        .corp-file-size { color: rgb(100 116 139); font-size: 0.7rem; white-space: nowrap; }
        .dark .corp-file-size { color: rgb(148 163 184); }
        .corp-file-remove { display: inline-flex; align-items: center; color: rgb(148 163 184); cursor: pointer; flex-shrink: 0; }
        .corp-file-remove:hover { color: rgb(220 38 38); }
        .corp-attach-error { margin-top: 0.3rem; color: rgb(220 38 38); font-size: 0.72rem; }
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
                            $attachCount = count($attachments);
                        @endphp
                        <tr
                            wire:key="weekly-hours-attach-row-{{ $row['id'] ?? 'new-' . $rowIndex }}"
                            class="corp-weekly-hours-attach-row"
                        >
                            <td class="corp-weekly-hours-attach-label">Files</td>
                            <td colspan="9" x-data="{ open: false }">
                                {{-- Inline badge bar: always one line --}}
                                <div class="corp-files-bar">
                                    @if ($attachCount > 0)
                                        <button type="button" @click="open = !open" class="corp-files-badge" :class="{ 'is-open': open }">
                                            <x-filament::icon icon="heroicon-m-paper-clip" class="h-3.5 w-3.5" />
                                            <span>{{ $attachCount }} file{{ $attachCount > 1 ? 's' : '' }} attached</span>
                                            <x-filament::icon icon="heroicon-m-chevron-right" class="h-3 w-3" />
                                        </button>
                                    @else
                                        <span class="corp-files-empty">No files attached</span>
                                    @endif

                                    @if ($editable)
                                        {{-- Compact icon-only upload button --}}
                                        <label class="corp-attach-icon-btn" wire:key="upload-{{ $rowIndex }}">
                                            <span wire:loading.remove wire:target="rowUploads.{{ $rowIndex }},uploadAttachment">
                                                <x-filament::icon icon="heroicon-m-arrow-up-tray" class="h-4 w-4" />
                                            </span>
                                            <span wire:loading wire:target="rowUploads.{{ $rowIndex }},uploadAttachment" class="inline-flex items-center justify-center">
                                                <span class="corp-attach-spinner"></span>
                                            </span>
                                            <input
                                                type="file"
                                                wire:model="rowUploads.{{ $rowIndex }}"
                                                wire:loading.attr="disabled"
                                                wire:target="rowUploads.{{ $rowIndex }},uploadAttachment"
                                            />
                                        </label>
                                        @if ($attachCount > 0)
                                            <button
                                                type="button"
                                                wire:click="uploadAttachment({{ $rowIndex }})"
                                                wire:loading.attr="disabled"
                                                wire:target="rowUploads.{{ $rowIndex }},uploadAttachment"
                                                class="corp-files-badge"
                                                style="background: rgb(37 99 235); color: #fff; border: none; cursor: pointer;"
                                            >
                                                <x-filament::icon icon="heroicon-m-check" class="h-3.5 w-3.5" />
                                                Attach
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="uploadAttachment({{ $rowIndex }})"
                                                wire:loading.attr="disabled"
                                                wire:target="rowUploads.{{ $rowIndex }},uploadAttachment"
                                                class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline"
                                            >
                                                Attach file
                                            </button>
                                        @endif
                                    @endif
                                </div>

                                {{-- Expanded file list (Alpine toggle) --}}
                                <div x-show="open" x-transition class="corp-files-expanded" style="display:none">
                                    @foreach ($attachments as $attachment)
                                        <div class="corp-file-item">
                                            <x-filament::icon icon="heroicon-m-document-text" class="h-3.5 w-3.5 text-slate-400 flex-shrink-0" />
                                            <a href="{{ $this->attachmentDownloadUrl($attachment['id']) }}" target="_blank" rel="noopener">
                                                {{ $attachment['name'] }}
                                            </a>
                                            <span class="corp-file-size">{{ $attachment['size'] }}</span>
                                            @if ($editable)
                                                <button
                                                    type="button"
                                                    wire:click="removeAttachment({{ $attachment['id'] }})"
                                                    wire:confirm="Remove this attachment?"
                                                    class="corp-file-remove"
                                                    aria-label="Remove attachment"
                                                >
                                                    <x-filament::icon icon="heroicon-m-x-mark" class="h-3.5 w-3.5" />
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @error('rowUploads.' . $rowIndex)
                                    <div class="corp-attach-error">{{ $message }}</div>
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
