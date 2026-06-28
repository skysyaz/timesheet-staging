<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WEEKLY TIME SHEET</title>
    <style>
        @page { size: 612pt 792pt; margin: 20pt 30pt 28pt 30pt; }
        body { font-family: 'Century Gothic', 'CenturyGothic', 'DejaVu Sans', sans-serif; font-weight: bold; font-size: 9pt; color: #000; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .ref { text-align: right; font-size: 7pt; margin-bottom: 2pt; font-weight: bold; }
        .logo { text-align: center; margin: 2pt 0 2pt; }
        .logo img { width: 91pt; height: 37pt; }
        .title { text-align: center; font-size: 12pt; font-weight: bold; margin: 4pt 0 8pt; }
        .info td { font-size: 8.5pt; white-space: nowrap; }
        .info .field-cell { border-bottom: 1px solid #000; min-width: 60pt; padding: 0 0 0 6pt; font-weight: normal; }
        .info .label { white-space: nowrap; padding-right: 2pt; }
        .info .colon { padding: 0 6pt 0 0; }
        .info .spacer { width: 24pt; }
        .hrs-table { border: 1px solid #000; margin-top: 16pt; }
        .hrs-table th { background: #d9d9d9; text-align: center; font-size: 7.5pt; font-weight: bold; padding: 3pt 2pt; border: 1px solid #000; }
        .hrs-table td { text-align: center; font-size: 8pt; padding: 7pt 2pt; border: 1px solid #000; font-weight: normal; }
        .hrs-table .day-col { width: 10%; }
        .hrs-table .date-col { width: 15%; }
        .hrs-table .task-col { width: 55%; }
        .hrs-table .hrs-col { width: 20%; }
        .total { text-align: right; font-size: 9pt; font-weight: bold; margin-top: 12pt; }
        .sig-spacer { height: 202pt; }
        .sig { margin-top: 0; }
        .sig td { text-align: center; width: 33%; padding: 0 4pt; }
        .sig .sig-label { font-size: 8pt; font-weight: bold; }
        .sig .sig-line { border-bottom: 1px dotted #000; height: 26pt; width: 100%; margin: 0; }
        .sig .sig-role { font-size: 7pt; margin-top: 4pt; }
        .sig .sig-field { font-size: 8pt; margin-top: 6pt; text-align: left; padding-left: 8pt; }
    </style>
</head>
<body>

<div class="ref">GHCD/SOP/RR/F08/Rev.02</div>

<div class="header" style="text-align:center; margin-bottom: 10pt;">
    <div class="logo"><img src="{{ public_path('logo.jpg') }}" alt="Company Logo"></div>
    <div class="title">WEEKLY TIME SHEET</div>
</div>

<table class="info">
    <tr>
        <td class="label">Name</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ $timesheet->user->name }}</td>
        <td style="width:24pt;"></td>
        <td class="label">Company</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ config('app.name') }}</td>
    </tr>
    <tr>
        <td class="label">Project</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ $timesheet->project->code ?? '' }}</td>
        <td></td>
        <td class="label">Week Ending</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ $timesheet->week_start->copy()->addDays(6)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label">Project Role</td>
        <td>:</td>
        <td class="field-cell">{{ $timesheet->project_role ?? '' }}</td>
        <td></td>
        <td class="label">Month</td>
        <td>:</td>
        <td class="field-cell">{{ $timesheet->week_start->format('F Y') }}</td>
    </tr>
</table>

<table class="hrs-table">
    <thead>
        <tr>
            <th class="day-col">DAY</th>
            <th class="date-col">DATE</th>
            <th class="task-col">ACTIVITY/TASK</th>
            <th class="hrs-col">HOURS</th>
        </tr>
    </thead>
    <tbody>
        @php
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $shortDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        @endphp
        @foreach ($days as $i => $day)
            <tr>
                <td>{{ $shortDays[$i] }}</td>
                <td>{{ $timesheet->week_start->copy()->addDays($i)->format('d/m/Y') }}</td>
                <td>{{ $timesheet->taskForDay($i) }}</td>
                <td>{{ isset($timesheet->hours[$i]) ? number_format($timesheet->hours[$i], 1) : '0.0' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="total">TOTAL NO. OF HOURS : {{ number_format($timesheet->totalHours(), 1) }}</div>

<div class="sig-spacer"></div>

<table class="sig">
        <tr>
            <td><div class="sig-label">Prepared By:</div></td>
            <td><div class="sig-label">Confirmed By:</div></td>
            <td><div class="sig-label">Approved By:</div></td>
        </tr>
        <tr>
            <td><div class="sig-line"></div></td>
            <td><div class="sig-line"></div></td>
            <td><div class="sig-line"></div></td>
        </tr>
        <tr>
            <td><div class="sig-role">(Staff)</div></td>
            <td><div class="sig-role">Project Manager</div></td>
            <td><div class="sig-role">Project Director</div></td>
        </tr>
        <tr>
            <td><div class="sig-field">Name : {{ $timesheet->preparedByName() }}</div></td>
            <td><div class="sig-field">Name : {{ $timesheet->pmApproverName() }}</div></td>
            <td><div class="sig-field">Name : {{ $timesheet->pdApproverName() }}</div></td>
        </tr>
        <tr>
            <td><div class="sig-field">Date : {{ $timesheet->preparedByDate() }}</div></td>
            <td><div class="sig-field">Date : {{ $timesheet->pmApproverDate() }}</div></td>
            <td><div class="sig-field">Date : {{ $timesheet->pdApproverDate() }}</div></td>
        </tr>
    </table>

</body>
</html>
