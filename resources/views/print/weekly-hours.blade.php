<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weekly Hours – {{ $export->user->name }}</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Inter, system-ui, sans-serif;
            font-size: 13px;
            color: #111827;
            margin: 0;
            padding: 24px;
            background: #fff;
        }

        .print-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 20px;
        }

        .print-toolbar button,
        .print-toolbar a {
            appearance: none;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .print-toolbar button.primary,
        .print-toolbar a.primary {
            background: #1b3860;
            border-color: #1b3860;
            color: #fff;
        }

        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-header img {
            height: 40px;
            margin-bottom: 8px;
        }

        .print-header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .print-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 24px;
            margin-bottom: 20px;
        }

        .print-meta div {
            display: flex;
            gap: 8px;
        }

        .print-meta strong {
            min-width: 88px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e5e7eb;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 6px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #f9fafb;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
        }

        th.text-left,
        td.text-left {
            text-align: left;
        }

        td.weekend,
        th.weekend {
            background: #fffbeb;
        }

        tfoot td {
            font-weight: 700;
            background: #f9fafb;
        }

        .empty {
            padding: 32px;
            text-align: center;
            color: #6b7280;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <a class="primary" href="{{ route('pdf.weekly-hours', ['user' => $export->user->id, 'weekStart' => $export->weekStart->toDateString()]) }}">Download PDF</a>
    </div>

    <div class="print-header">
        <img src="{{ asset('logo.webp') }}" alt="{{ config('app.name') }}">
        <h1>Weekly Hours</h1>
    </div>

    <div class="print-meta">
        <div><strong>Name</strong><span>{{ $export->user->name }}</span></div>
        <div><strong>Company</strong><span>{{ config('app.name') }}</span></div>
        <div><strong>Week</strong><span>{{ $export->weekLabel() }}</span></div>
        <div><strong>Week ending</strong><span>{{ $export->weekStart->copy()->addDays(6)->format('d M Y') }}</span></div>
    </div>

    @if ($export->rows() === [])
        <div class="empty">No hours recorded for this week.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th class="text-left">Project</th>
                    <th class="text-left">Activity</th>
                    @foreach ($export->dayHeaders() as $index => $label)
                        <th @class(['weekend' => $index >= 5])>{{ $label }}</th>
                    @endforeach
                    <th>Duration</th>
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

    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () {
                window.print();
            }, 250);
        });
    </script>
</body>
</html>
