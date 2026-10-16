
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CarbonWise Analytics Report</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 18mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1f2937;
        }

        .header {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 14px;
            border-bottom: 3px solid #166534;
        }

        h1 {
            margin: 0 0 8px;
            color: #166534;
            font-size: 22pt;
            font-weight: bold;
        }

        .subtitle {
            margin: 0;
            font-size: 11pt;
            color: #4b5563;
        }

        .report-info {
            margin-top: 12px;
            font-size: 10pt;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 18px;
        }

        th {
            background-color: #166534;
            color: #ffffff;
            border: 1px solid #14532d;
            padding: 10px 7px;
            text-align: left;
            font-size: 10pt;
            font-weight: bold;
        }

        td {
            border: 1px solid #d1d5db;
            padding: 9px 7px;
            font-size: 10pt;
            line-height: 1.4;
            overflow-wrap: break-word;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background-color: #f0fdf4;
        }

        .user-column {
            width: 23%;
            word-wrap: break-word;
        }

        .number-column {
            width: 11%;
        }

        .total-column {
            width: 13%;
            font-weight: bold;
            color: #166534;
        }

        .date-column {
            width: 12%;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 9pt;
            color: #6b7280;
        }

        .empty-row {
            padding: 22px;
            text-align: center;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>CarbonWise Analytics Report</h1>

        <p class="subtitle">
            Carbon Emissions Summary
        </p>

        <p class="report-info">
            Generated on: {{ now()->format('F d, Y h:i A') }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="user-column">User</th>
                <th class="number-column">Transportation</th>
                <th class="number-column">Electricity</th>
                <th class="number-column">Food</th>
                <th class="number-column">Waste</th>
                <th class="total-column">Total Emission (kg CO₂e)</th>
                <th class="date-column">Date</th>
            </tr>
        </thead>

        <tbody>
            @forelse($records as $record)
                <tr>
                    <td class="user-column">
                        {{ $record->g_suite }}
                    </td>

                    <td>
                        {{ number_format((float) $record->transportation, 2) }}
                    </td>

                    <td>
                        {{ number_format((float) $record->electricity, 2) }}
                    </td>

                    <td>
                        {{ number_format((float) $record->food, 2) }}
                    </td>

                    <td>
                        {{ number_format((float) $record->waste, 2) }}
                    </td>

                    <td class="total-column">
                        {{ number_format((float) $record->total_emission, 2) }}
                    </td>

                    <td>
                        {{ $record->record_date
                            ? \Carbon\Carbon::parse($record->record_date)->format('M d, Y')
                            : 'N/A' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-row">
                        No carbon records found for this report.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        CarbonWise Sustainability Monitoring System
    </div>

</body>
</html>
