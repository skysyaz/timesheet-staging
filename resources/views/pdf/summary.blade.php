<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $builder->title() }}</title>
    <style>
        @page { size: 612pt 792pt; margin: 24pt 36pt 32pt 36pt; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Century Gothic', 'CenturyGothic', 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #000;
            margin: 0;
            padding: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }

        .header { text-align: center; margin-bottom: 16pt; }
        .logo img { width: 91pt; height: auto; max-height: 40pt; }
        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 10pt 0 0;
            letter-spacing: 0.5pt;
        }

        .info { margin-top: 18pt; }
        .info td {
            font-size: 8.5pt;
            padding: 4pt 0;
            vertical-align: bottom;
        }
        .info .label {
            width: 72pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .info .colon { width: 8pt; text-align: center; }
        .info .field-cell {
            border-bottom: 1px solid #000;
            min-width: 120pt;
            padding: 0 4pt 2pt 6pt;
            font-weight: normal;
        }
        .info .spacer { width: 24pt; }

        .data-table {
            border: 1px solid #000;
            margin-top: 24pt;
        }
        .data-table th {
            background: #d9d9d9;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            padding: 6pt 4pt;
            border: 1px solid #000;
        }
        .data-table td {
            font-size: 8.5pt;
            padding: 8pt 6pt;
            border: 1px solid #000;
            font-weight: normal;
        }
        .data-table .label-col { width: 68%; text-align: left; }
        .data-table .hrs-col { width: 32%; text-align: center; }
        .data-table tfoot td {
            font-weight: bold;
            background: #efefef;
        }

        .total {
            text-align: right;
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 10pt;
        }

        .sig { margin-top: 36pt; page-break-inside: avoid; }
        .sig td {
            text-align: center;
            width: 33.33%;
            padding: 0 6pt;
            vertical-align: top;
        }
        .sig .sig-label { font-size: 8pt; font-weight: bold; margin-bottom: 4pt; }
        .sig .sig-line {
            border-bottom: 1px dotted #000;
            height: 28pt;
            width: 100%;
        }
        .sig .sig-role { font-size: 7pt; margin-top: 4pt; }
        .sig .sig-field { font-size: 8pt; margin-top: 6pt; text-align: left; padding-left: 8pt; }
    </style>
</head>
<body>

<div class="header">
    <div class="logo"><img src="{{ public_path('logo.jpg') }}" alt="Company Logo"></div>
    <div class="title">{{ $builder->title() }}</div>
</div>

<table class="info">
    <tr>
        <td class="label">Name</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ $userName ?? '' }}</td>
        <td class="spacer"></td>
        <td class="label">Company</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ config('app.name') }}</td>
    </tr>
    <tr>
        <td class="label">Project</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ $project->name ?? 'All Projects' }}</td>
        <td class="spacer"></td>
        <td class="label">Period</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ $builder->periodLabel() }}</td>
    </tr>
    <tr>
        <td class="label">Status</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ $builder->statusLabel() ?? 'All Statuses' }}</td>
        <td class="spacer"></td>
        <td class="label">Generated</td>
        <td class="colon">:</td>
        <td class="field-cell">{{ now()->format('d/m/Y') }}</td>
    </tr>
</table>

<table class="data-table">
    <thead>
        <tr>
            <th class="label-col">{{ $builder->dataColumnLabel() }}</th>
            <th class="hrs-col">Hours</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $row)
            <tr>
                <td class="label-col">{{ $row['label'] }}</td>
                <td class="hrs-col">{{ number_format($row['hours'], 1) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" style="text-align:center;">No data found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
    @if (count($data) > 0)
        <tfoot>
            <tr>
                <td class="label-col">Total</td>
                <td class="hrs-col">{{ number_format($totalHours, 1) }}</td>
            </tr>
        </tfoot>
    @endif
</table>

<div class="total">TOTAL HOURS : {{ number_format($totalHours, 1) }}</div>

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
        <td><div class="sig-role">(Project Manager)</div></td>
        <td><div class="sig-role">(Project Director)</div></td>
    </tr>
    <tr>
        <td><div class="sig-field">Name :</div></td>
        <td><div class="sig-field">Name :</div></td>
        <td><div class="sig-field">Name :</div></td>
    </tr>
    <tr>
        <td><div class="sig-field">Date :</div></td>
        <td><div class="sig-field">Date :</div></td>
        <td><div class="sig-field">Date :</div></td>
    </tr>
</table>

</body>
</html>
