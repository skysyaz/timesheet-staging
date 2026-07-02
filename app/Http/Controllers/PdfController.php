<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\TimesheetAccess;
use App\Support\TimesheetSummaryBuilder;
use App\Support\WeeklyHoursExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PdfController extends Controller
{
    public function weekly(Timesheet $timesheet)
    {
        $user = auth()->user();

        if (! TimesheetAccess::userCanViewTimesheet($user, $timesheet)) {
            abort(403);
        }

        $timesheet->loadMissing(['user', 'project', 'approvalLogs.user']);

        $pdf = Pdf::loadView('pdf.weekly', compact('timesheet'));
        $filename = sprintf(
            'timesheet_%s_%s.pdf',
            $timesheet->user->name,
            $timesheet->week_start->format('Y-m-d')
        );

        AuditLogger::log('Weekly timesheet PDF exported', $timesheet, [
            'export' => 'weekly_pdf',
            'filename' => $filename,
        ]);

        return $pdf->download($filename);
    }

    public function weeklyHours(User $user, string $weekStart)
    {
        $export = WeeklyHoursExport::for($user, $weekStart, auth()->user());

        $pdf = Pdf::loadView('pdf.weekly-hours', [
            'export' => $export,
        ]);

        AuditLogger::log('Weekly hours PDF exported', null, [
            'export' => 'weekly_hours_pdf',
            'user_id' => $export->user->id,
            'week_start' => $export->weekStart->toDateString(),
            'filename' => $export->filename(),
        ]);

        return $pdf->download($export->filename());
    }

    public function weeklyHoursPrint(User $user, string $weekStart)
    {
        $export = WeeklyHoursExport::for($user, $weekStart, auth()->user());

        return view('print.weekly-hours', [
            'export' => $export,
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'groupBy' => ['nullable', 'string', Rule::in(TimesheetAccess::SUMMARY_GROUP_BY)],
            'status' => ['nullable', 'string', Rule::in(TimesheetAccess::TIMESHEET_STATUSES)],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'projectId' => ['nullable', 'integer', 'exists:projects,id'],
            'userId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (filled($validated['projectId'] ?? null)) {
            $project = Project::query()->find($validated['projectId']);

            if (! TimesheetAccess::userCanAccessProject(auth()->user(), $project)) {
                abort(403);
            }
        }

        $builder = TimesheetSummaryBuilder::fromValidated($validated);
        $data = $builder->groupedData();
        $project = $builder->resolvedProject();

        $pdf = Pdf::loadView('pdf.summary', [
            'data' => $data,
            'totalHours' => $builder->totalHours(),
            'totalRegularHours' => $builder->totalRegularHours(),
            'totalOvertimeHours' => $builder->totalOvertimeHours(),
            'totalWeightedHours' => $builder->totalWeightedHours(),
            'builder' => $builder,
            'project' => $project,
            'userName' => auth()->user()->name,
        ]);

        $filename = 'timesheet_summary_' . now()->format('Y-m-d') . '.pdf';

        AuditLogger::log('Timesheet summary PDF exported', null, [
            'export' => 'summary_pdf',
            'filters' => AuditLogger::redactProperties($validated),
        ]);

        return $pdf->download($filename);
    }
}
