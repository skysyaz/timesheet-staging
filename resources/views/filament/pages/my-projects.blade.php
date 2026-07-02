<x-filament-panels::page>
    <div class="corp-my-projects">
        @forelse ($this->getAssignedProjects() as $entry)
            @php
                /** @var \App\Models\Project $project */
                $project = $entry['project'];
            @endphp
            <div class="corp-my-projects-card">
                <div class="corp-my-projects-card-header">
                    <div>
                        <div class="corp-my-projects-code">{{ $project->code }}</div>
                        <h2 class="corp-my-projects-name">{{ $project->name }}</h2>
                        <p class="corp-my-projects-role">Your role: {{ $entry['role'] }}</p>
                    </div>
                    <span class="corp-my-projects-badge corp-my-projects-badge-{{ $entry['schedule_color'] }}">
                        {{ $entry['schedule_label'] }}
                    </span>
                </div>

                @if ($project->description)
                    <p class="corp-my-projects-description">{{ $project->description }}</p>
                @endif

                <div class="corp-my-projects-approvers" aria-label="Project approval contacts">
                    <div class="corp-my-projects-approver">
                        <span class="corp-my-projects-approver-badge corp-my-projects-approver-badge-pm">Project Manager</span>
                        <span @class([
                            'corp-my-projects-approver-name',
                            'is-unassigned' => blank($project->projectManager?->name),
                        ])>
                            {{ $project->projectManager?->name ?? 'Not assigned' }}
                        </span>
                    </div>
                    <div class="corp-my-projects-approver">
                        <span class="corp-my-projects-approver-badge corp-my-projects-approver-badge-pgm">Program Manager</span>
                        <span @class([
                            'corp-my-projects-approver-name',
                            'is-unassigned' => blank($project->programManager?->name),
                        ])>
                            {{ $project->programManager?->name ?? 'Not assigned' }}
                        </span>
                    </div>
                </div>

                <div class="corp-my-projects-metrics">
                    <div>
                        <span class="corp-my-projects-metric-label">Start</span>
                        <span class="corp-my-projects-metric-value">{{ $project->start_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="corp-my-projects-metric-label">Deadline</span>
                        <span class="corp-my-projects-metric-value">{{ $project->end_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="corp-my-projects-metric-label">Days remaining</span>
                        <span class="corp-my-projects-metric-value">
                            {{ $entry['days_remaining'] !== null ? $entry['days_remaining'] . ' days' : '—' }}
                        </span>
                    </div>
                    <div>
                        <span class="corp-my-projects-metric-label">Your hours logged</span>
                        <span class="corp-my-projects-metric-value">{{ number_format($entry['hours_logged'], 1) }}h</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="corp-my-projects-empty">
                <p>You are not assigned to any active projects yet.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
