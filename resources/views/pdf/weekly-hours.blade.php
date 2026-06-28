<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Weekly Hours – {{ $export->user->name }}</title>
    <style>
        @page { size: landscape; margin: 16pt 20pt; }
        body { font-family: 'Century Gothic', 'CenturyGothic', 'DejaVu Sans', sans-serif; font-size: 8pt; color: #000; margin: 0; padding: 0; }
        .ref { text-align: right; font-size: 7pt; margin-bottom: 4pt; font-weight: bold; }
        .header { text-align: center; margin-bottom: 10pt; }
        .logo img { width: 80pt; height: 32pt; }
        .title { font-size: 12pt; font-weight: bold; margin-top: 4pt; }
        .meta { width: 100%; margin-bottom: 10pt; font-size: 8pt; }
        .meta td { padding: 2pt 0; vertical-align: top; }
        .meta .label { font-weight: bold; width: 70pt; }
        .meta .value { border-bottom: 1px solid #000; padding-left: 6pt; }
        table.grid { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        table.grid th { background: #d9d9d9; border: 1px solid #000; padding: 4pt 3pt; text-align: center; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
        table.grid td { border: 1px solid #000; padding: 5pt 3pt; text-align: center; font-size: 8pt; vertical-align: middle; }
        table.grid td.text-left { text-align: left; padding-left: 6pt; }
        table.grid th.text-left { text-align: left; padding-left: 6pt; }
        table.grid td.weekend, table.grid th.weekend { background: #fff3cd; }
        table.grid tfoot td { font-weight: bold; background: #f3f4f6; }
        .empty { padding: 16pt; text-align: center; font-style: italic; }
    </style>
</head>
<body>

<div class="ref">GHCD/SOP/RR/F08/Rev.02</div>

<div class="header">
    <div class="logo"><img src="{{ public_path('logo.jpg') }}" alt="Company Logo"></div>
    <div class="title">WEEKLY HOURS</div>
</div>

<table class="meta">
    <tr>
        <td class="label">Name</td>
        <td class="value">{{ $export->user->name }}</td>
        <td style="width: 24pt;"></td>
        <td class="label">Company</td>
        <td class="value">{{ config('app.name') }}</td>
    </tr>
    <tr>
        <td class="label">Week</td>
        <td class="value">{{ $export->weekLabel() }}</td>
        <td></td>
        <td class="label">Week Ending</td>
        <td class="value">{{ $export->weekStart->copy()->addDays(6)->format('d/m/Y') }}</td>
    </tr>
</table>

@if ($export->rows() === [])
    <div class="empty">No hours recorded for this week.</div>
@else
    <table class="grid">
        <thead>
            <tr>
                <th class="text-left" style="width: 18%;">Project</th>
                <th class="text-left" style="width: 14%;">Activity</th>
                @foreach ($export->dayHeaders() as $index => $label)
                    <th @class(['weekend' => $index >= 5])>{{ $label }}</th>
                @endforeach
                <th style="width: 8%;">Duration</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($export->rows() as $row)
                <tr>
                    <td class="text-left">{{ $row['project_name'] }}</td>
                    <td class="text-left">{{ $row['activity'] ?: '—' }}</td>
                    @foreach (range(0, 6) as $dayIndex)
                        <td @class(['weekend' => $dayIndex >= 5])>{{ $export->formatHours($row['hours'][$dayIndex] ?? 0) }}</td>
                    @endforeach
                    <td>{{ $export->rowDuration($row) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="text-left" colspan="2">Total</td>
                @foreach ($export->columnTotals() as $index => $total)
                    <td @class(['weekend' => $index >= 5])>{{ $total }}</td>
                @endforeach
                <td>{{ $export->grandTotal() }}</td>
            </tr>
        </tfoot>
    </table>
@endif

</body>
</html>
