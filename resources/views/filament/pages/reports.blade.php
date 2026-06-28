<x-filament-panels::page>
    {{-- Summary cards --}}
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="corp-stat-card" style="border-bottom-color: rgb(29 78 216);">
            <div class="flex flex-col items-center justify-center gap-1.5 py-1 text-center">
                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400">Entries</p>
                <p class="text-3xl font-bold leading-none tabular-nums text-slate-900 dark:text-white">{{ $this->getReportCount() }}</p>
            </div>
        </div>
        <div class="corp-stat-card" style="border-bottom-color: rgb(4 120 87);">
            <div class="flex flex-col items-center justify-center gap-1.5 py-1 text-center">
                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400">Total Hours</p>
                <p class="text-3xl font-bold leading-none tabular-nums text-slate-900 dark:text-white">
                    {{ number_format($this->getTotalHours(), 1) }}<span class="ml-0.5 text-lg font-medium text-slate-400">h</span>
                </p>
            </div>
        </div>
        <div class="corp-stat-card" style="border-bottom-color: rgb(180 83 9);">
            <div class="flex flex-col items-center justify-center gap-1.5 py-1 text-center">
                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400">Average</p>
                <p class="text-3xl font-bold leading-none tabular-nums text-slate-900 dark:text-white">
                    {{ $this->getReportCount() > 0 ? number_format($this->getTotalHours() / $this->getReportCount(), 1) : '0' }}<span class="ml-0.5 text-lg font-medium text-slate-400">h</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <x-filament::section
        heading="Report Filters"
        description="Refine your report by date range, project, and grouping. Project managers and directors see analytics for every project they are assigned to."
    >
        <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="min-w-0 space-y-2">
                <label for="reportType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Group By
                </label>
                <select id="reportType" wire:model.live="reportType"
                    class="fi-select-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="project">By Project</option>
                    <option value="member">By Member</option>
                    <option value="week">By Week</option>
                    <option value="month">By Month</option>
                </select>
            </div>
            <div class="min-w-0 space-y-2">
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    From Date
                </label>
                <input id="dateFrom" type="date" wire:model.live="dateFrom"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            </div>
            <div class="min-w-0 space-y-2">
                <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    To Date
                </label>
                <input id="dateTo" type="date" wire:model.live="dateTo"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            </div>
            <div class="min-w-0 space-y-2">
                <label for="projectId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Project
                </label>
                <select id="projectId" wire:model.live="projectId"
                    class="fi-select-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">All Projects</option>
                    @foreach($this->getProjects() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    {{-- Results --}}
    <x-filament::section
        class="mt-6"
        heading="{{ match($reportType) {
            'project' => 'Hours by Project',
            'member' => 'Hours by Member',
            'week' => 'Hours by Week',
            'month' => 'Hours by Month',
            default => 'Report Results',
        } }}"
        description="{{ $this->getReportCount() }} {{ $this->getReportCount() === 1 ? 'entry' : 'entries' }} · {{ number_format($this->getTotalHours(), 1) }} total hours"
    >
        @php $data = $this->getReportData(); $total = $this->getTotalHours(); @endphp

        @if(count($data) > 0)
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="corp-reports-table w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/80">
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ match($reportType) {
                                    'member' => 'Member',
                                    'week' => 'Week',
                                    'month' => 'Month',
                                    default => 'Project',
                                } }}
                            </th>
                            <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Hours</th>
                            <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Share</th>
                            <th class="min-w-[10rem] px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $row)
                            @php $pct = $total > 0 ? ($row['hours'] / $total * 100) : 0; @endphp
                            <tr>
                                <td class="max-w-xs truncate px-5 py-4 font-medium text-gray-900 dark:text-white" title="{{ $row['label'] }}">{{ $row['label'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($row['hours'], 1) }}h</td>
                                <td class="whitespace-nowrap px-5 py-4 tabular-nums text-gray-500 dark:text-gray-400">{{ number_format($pct, 1) }}%</td>
                                <td class="px-5 py-4">
                                    <div class="corp-progress-track">
                                        <div class="corp-progress-fill" style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 bg-gray-50/50 dark:border-gray-700 dark:bg-gray-900/50">
                            <td class="px-5 py-3.5 font-semibold text-gray-900 dark:text-white">Total</td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($total, 1) }}h</td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-semibold text-gray-500">100%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 py-16 dark:border-gray-700">
                <x-heroicon-o-chart-bar-square class="mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" />
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No data matches the selected filters</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Try adjusting the date range or project filter.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
