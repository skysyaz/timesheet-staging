<x-filament-widgets::widget>
    <div class="corp-dashboard-greeting-card">
        <p class="corp-welcome-date">{{ $this->getTodayLabel() }}</p>
        <h2 class="corp-welcome-title">
            {{ $this->getGreeting() }}, {{ auth()->user()->name }}
        </h2>
        <p class="corp-welcome-subtitle">
            @if (auth()->user()->isEmployee())
                Track your weekly hours, submit timesheets, and monitor approval status.
            @elseif (auth()->user()->isApprover())
                Review team submissions, approve timesheets, and stay on top of project hours.
            @else
                Manage timesheets, projects, and team members across your organization.
            @endif
        </p>

        <div class="corp-welcome-actions">
            @if (auth()->user()->isEmployee())
                <a href="{{ $this->getCreateUrl() }}" class="corp-btn corp-btn-primary">
                    <x-heroicon-m-plus class="h-3.5 w-3.5 shrink-0" />
                    New Timesheet
                </a>
            @endif

            <a href="{{ $this->getTimesheetsUrl() }}" class="corp-btn corp-btn-secondary">
                <x-heroicon-m-clock class="h-3.5 w-3.5 shrink-0" />
                View Timesheets
            </a>

            @if ($this->getPendingCount() > 0)
                <span class="corp-pending-badge">
                    <x-heroicon-m-exclamation-circle class="h-3.5 w-3.5 shrink-0" />
                    {{ $this->getPendingCount() }} pending
                </span>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
