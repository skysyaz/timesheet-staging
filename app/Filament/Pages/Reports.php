<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Support\TimesheetAccess;
use App\Support\TimesheetSummaryBuilder;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Reports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Analytics & Reports';

    protected static ?string $slug = 'reports';

    protected string $view = 'filament.pages.reports';

    public string $reportType = 'project';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $projectId = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportSummary')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => $this->getExportUrl())
                ->openUrlInNewTab(),
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->action('exportCsv'),
        ];
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->getReportData();
        $total = $this->getTotalHours();
        $filename = 'timesheet-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($data, $total): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [$this->summaryBuilder()->dataColumnLabel(), 'Hours', 'Share %']);

            foreach ($data as $row) {
                $share = $total > 0 ? round(($row['hours'] / $total) * 100, 1) : 0;
                fputcsv($handle, [$row['label'], number_format($row['hours'], 1, '.', ''), $share]);
            }

            fputcsv($handle, ['Total', number_format($total, 1, '.', ''), $total > 0 ? 100 : 0]);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function getProjects(): array
    {
        $user = auth()->user();

        $query = Project::query()->where('status', 'active');

        if ($user) {
            TimesheetAccess::scopeProjectsForUser($query, $user);
        }

        return $query->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        if ($user?->isApprover() && ! $user->isAdmin()) {
            return 'Project Analytics';
        }

        return 'Analytics & Reports';
    }

    public function summaryBuilder(): TimesheetSummaryBuilder
    {
        return TimesheetSummaryBuilder::fromReports(
            $this->reportType,
            $this->dateFrom,
            $this->dateTo,
            $this->projectId,
        );
    }

    public function getExportUrl(): string
    {
        return $this->summaryBuilder()->exportUrl();
    }

    public function getReportData(): array
    {
        return $this->summaryBuilder()->groupedData();
    }

    public function getTotalHours(): float
    {
        return $this->summaryBuilder()->totalHours();
    }

    public function getReportCount(): int
    {
        return count($this->getReportData());
    }
}
