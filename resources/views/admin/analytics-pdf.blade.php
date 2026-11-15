<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CarbonWise Analytics Report</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 9mm 11mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            line-height: 1.35;
            color: #1f2937;
        }

        .header {
            text-align: center;
            padding-bottom: 8px;
            margin-bottom: 10px;
            border-bottom: 3px solid #166534;
        }

        h1 {
            margin: 0 0 4px;
            color: #166534;
            font-size: 19pt;
        }

        .subtitle {
            margin: 0;
            font-size: 9pt;
            color: #4b5563;
        }

        .report-info {
            margin: 4px 0 0;
            font-size: 7.5pt;
            color: #6b7280;
        }

        .section-title {
            color: #166534;
            font-size: 11pt;
            margin: 9px 0 5px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #166534;
            color: #ffffff;
            border: 1px solid #14532d;
            padding: 6px;
            text-align: left;
            font-size: 7.5pt;
        }

        td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 7.5pt;
            vertical-align: middle;
        }

        /* SUMMARY CARDS */

        .summary-grid {
            border-collapse: separate;
            border-spacing: 5px;
            margin: 0 -5px;
        }

        .summary-grid td {
            width: 33.33%;
            padding: 7px 9px;
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
        }

        .summary-label {
            font-size: 7pt;
            color: #6b7280;
        }

        .summary-value {
            font-size: 13pt;
            font-weight: bold;
            color: #166534;
            margin-top: 2px;
        }

        .summary-unit {
            font-size: 7pt;
            color: #6b7280;
        }

        /* COLOR LEGEND - CLEAN FOUR-COLUMN LAYOUT */

        .legend-box {
            margin-top: 8px;
            margin-bottom: 10px;
            padding: 7px 9px;
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
        }

        .legend-title {
            margin: 0 0 7px;
            color: #166534;
            font-size: 9pt;
            font-weight: bold;
        }

        .legend-items {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .legend-item {
            display: table-cell;
            width: 25%;
            padding: 2px 5px;
            vertical-align: top;
            font-size: 7pt;
            line-height: 1.4;
        }

        .legend-label {
            display: block;
            margin-bottom: 3px;
            font-weight: bold;
            white-space: nowrap;
        }

        .legend-color {
            display: inline-block;
            width: 9px;
            height: 9px;
            margin-right: 4px;
        }

        .legend-description {
            display: block;
            color: #1f2937;
            font-size: 6.8pt;
            line-height: 1.35;
        }

        .legend-note {
            margin: 5px 0 0;
            font-size: 6.5pt;
            color: #6b7280;
            line-height: 1.35;
        }

        /* EMISSION COLORS */

        .emission-high {
            color: #dc2626;
            font-weight: bold;
        }

        .emission-moderate {
            color: #ea580c;
            font-weight: bold;
        }

        .emission-low {
            color: #15803d;
            font-weight: bold;
        }

        .emission-none {
            color: #808080;
            font-weight: bold;
        }

        /* RANKING TABLE */

        .ranking-table {
            table-layout: fixed;
        }

        .ranking-table th,
        .ranking-table td {
            padding: 5px 6px;
        }

        .grand-total-row td {
            background-color: #ecfdf5;
            color: #166534;
            font-weight: bold;
            border-top: 2px solid #166534;
        }

        /* DETAIL RECORDS */

        .records-table {
            table-layout: fixed;
        }

        .records-table tbody tr:nth-child(even) {
            background-color: #f0fdf4;
        }

        .user-column {
            width: 25%;
            word-wrap: break-word;
        }

        .number-column {
            width: 12%;
        }

        .total-column {
            width: 15%;
            font-weight: bold;
        }

        .date-column {
            width: 14%;
        }

        /* INTERPRETATION */

        .interpretation-box {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #d1d5db;
        }

        .interpretation-box h3 {
            margin: 0 0 5px;
            color: #166534;
            font-size: 9pt;
        }

        .interpretation-box p {
            margin: 4px 0;
            font-size: 8pt;
            line-height: 1.5;
        }

        .recommendation-box {
            margin-top: 10px;
            padding: 10px;
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
        }

        .recommendation-box h3 {
            margin: 0 0 5px;
            color: #1e40af;
            font-size: 9pt;
        }

        .recommendation-box p {
            margin: 0;
            font-size: 8pt;
        }

        .empty-row {
            text-align: center;
            padding: 15px;
            color: #6b7280;
        }

        .page-break {
            page-break-before: always;
        }

        .footer {
            margin-top: 15px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 7pt;
            color: #6b7280;
        }
    </style>
</head>

<body>

@php
    /*
     * Calculate report statistics from exported records.
     */

    $recordCount = $records->count();

    $totalEmissions = (float) $records->sum('total_emission');

    $averageEmissionPerRecord = $recordCount > 0
        ? $totalEmissions / $recordCount
        : 0;

    $transportation = (float) $records->sum('transportation');
    $electricity = (float) $records->sum('electricity');
    $food = (float) $records->sum('food');

    $sources = [
        'Transportation' => $transportation,
        'Electricity' => $electricity,
        'Food' => $food,
    ];

    $combinedSourceTotal = array_sum($sources);

    $sourcePercentages = [];

    foreach ($sources as $source => $value) {
        $sourcePercentages[$source] = $combinedSourceTotal > 0
            ? ($value / $combinedSourceTotal) * 100
            : 0;
    }

    // Sort sources from highest to lowest.
    $rankedSources = $sources;
    arsort($rankedSources);

    // Determine the highest contributing source.
    $highestSource = $combinedSourceTotal > 0
        ? array_key_first($rankedSources)
        : null;

    $highestPercentage = $highestSource !== null
        ? $sourcePercentages[$highestSource]
        : 0;

    // Compare source totals against total_emission.
    $emissionDifference = $totalEmissions - $combinedSourceTotal;

    /*
     * Color classification based on source contribution.
     */
    $getEmissionClass = function ($value, $total) {

        if ($value <= 0 || $total <= 0) {
            return 'emission-none';
        }

        $percentage = ($value / $total) * 100;

        if ($percentage >= 50) {
            return 'emission-high';
        }

        if ($percentage >= 25) {
            return 'emission-moderate';
        }

        return 'emission-low';
    };

    /*
     * Color total emissions relative to the average emission
     * per exported record.
     *
     * Red:    150% or more of average
     * Orange: 100% to below 150% of average
     * Green:  Above zero but below average
     * Gray:   Zero emission
     */
    $getTotalEmissionClass = function ($value, $average) {

        if ($value <= 0) {
            return 'emission-none';
        }

        if ($average <= 0) {
            return 'emission-low';
        }

        $ratio = $value / $average;

        if ($ratio >= 1.5) {
            return 'emission-high';
        }

        if ($ratio >= 1.0) {
            return 'emission-moderate';
        }

        return 'emission-low';
    };

    /*
     * Overall interpretation.
     */
    if ($recordCount > 0) {
        $totalInterpretation =
            "The report contains {$recordCount} carbon records with " .
            number_format($totalEmissions, 2) .
            " kg CO2e in total emissions. The average emission per record is " .
            number_format($averageEmissionPerRecord, 2) .
            " kg CO2e.";
    } else {
        $totalInterpretation = 'No carbon records are available for this report.';
    }

    /*
     * Source interpretation.
     */
    if ($highestSource !== null) {
        $interpretation =
            "{$highestSource} ranks first among the three monitored sources, " .
            "contributing " . number_format($highestPercentage, 2) .
            "% of the combined source emissions. This indicates that it has " .
            "the largest share of recorded emissions in this report.";
    } else {
        $interpretation = 'No source emissions are available for comparison.';
    }

    /*
     * Compare all three source categories.
     */
    $sourceComparisonInterpretation =
        "Transportation contributed " .
        number_format($sourcePercentages['Transportation'], 2) .
        "%, Electricity contributed " .
        number_format($sourcePercentages['Electricity'], 2) .
        "%, and Food contributed " .
        number_format($sourcePercentages['Food'], 2) .
        "% of the combined source emissions.";

    /*
     * Suggested mitigation actions.
     */
    $recommendations = [
        'Transportation' =>
            'Encourage public transportation, carpooling, walking, ' .
            'cycling, and more efficient travel practices.',

        'Electricity' =>
            'Promote energy-efficient appliances, switch off unused ' .
            'equipment, and practice responsible electricity use.',

        'Food' =>
            'Encourage sustainable food choices, reduce food waste, ' .
            'and promote responsible food consumption.',
    ];

    $recommendation = $highestSource !== null
        ? $recommendations[$highestSource]
        : 'No recommendation can be generated because no emission data is available.';
@endphp


{{-- =================================================
     PAGE 1: SUMMARY, LEGEND AND RANKINGS
================================================= --}}

<div class="header">

    <h1>CarbonWise Analytics Report</h1>

    <p class="subtitle">
        Carbon Emissions Summary and Interpretation
    </p>

    <p class="report-info">
        Generated on: {{ now()->format('F d, Y h:i A') }}
        &nbsp; | &nbsp;
        Total Exported Records: {{ number_format($recordCount) }}
        &nbsp; | &nbsp;
        Coverage: Latest {{ number_format($recordCount) }} Carbon Records
    </p>

</div>


{{-- OVERALL EMISSION SUMMARY --}}

<h2 class="section-title">
    Overall Emission Summary
</h2>

<table class="summary-grid">
    <tr>

        <td>
            <div class="summary-label">Total Emissions</div>

            <div class="summary-value">
                {{ number_format($totalEmissions, 2) }}
            </div>

            <div class="summary-unit">kg CO₂e</div>
        </td>

        <td>
            <div class="summary-label">Average Emissions per Record</div>

            <div class="summary-value">
                {{ number_format($averageEmissionPerRecord, 2) }}
            </div>

            <div class="summary-unit">kg CO₂e per record</div>
        </td>

        <td>
            <div class="summary-label">Number of Carbon Records</div>

            <div class="summary-value">
                {{ number_format($recordCount) }}
            </div>

            <div class="summary-unit">Exported records</div>
        </td>

    </tr>
</table>


{{-- COLOR LEGEND - ABOVE RANKINGS --}}

<div class="legend-box">

    <h3 class="legend-title">
        Color Legend: Emission Contribution Levels
    </h3>

    <div class="legend-items">

        <div class="legend-item">
            <span class="legend-label" style="color: #dc2626;">
                <span class="legend-color" style="background-color: #dc2626;"></span>Red
            </span>

            <span class="legend-description">
                High: 50% or higher
            </span>
        </div>

        <div class="legend-item">
            <span class="legend-label" style="color: #ea580c;">
                <span class="legend-color" style="background-color: #ea580c;"></span>Orange
            </span>

            <span class="legend-description">
                Moderate: 25% to below 50%
            </span>
        </div>

        <div class="legend-item">
            <span class="legend-label" style="color: #15803d;">
                <span class="legend-color" style="background-color: #15803d;"></span>Green
            </span>

            <span class="legend-description">
                Low: Above 0% to below 25%
            </span>
        </div>

        <div class="legend-item">
            <span class="legend-label" style="color: #808080;">
                <span class="legend-color" style="background-color: #808080;"></span>Gray
            </span>

            <span class="legend-description">
                No contribution: 0%
            </span>
        </div>

    </div>

    <p class="legend-note">
        Colors indicate relative contribution to combined source emissions,
        not official environmental safety levels.
    </p>

</div>


{{-- EMISSION SOURCE RANKINGS --}}

<h2 class="section-title">
    Emission Source Rankings
</h2>

<table class="ranking-table">

    <thead>
        <tr>
            <th style="width: 10%;">Rank</th>
            <th style="width: 30%;">Emission Source</th>
            <th style="width: 30%;">Total Emissions (kg CO₂e)</th>
            <th style="width: 30%;">Contribution</th>
        </tr>
    </thead>

    <tbody>

        @forelse($rankedSources as $source => $value)

            @php
                $percentage = $sourcePercentages[$source] ?? 0;

                $class = $getEmissionClass(
                    $value,
                    $combinedSourceTotal
                );
            @endphp

            <tr>
                <td>{{ $loop->iteration }}</td>

                <td>
                    <strong>{{ $source }}</strong>
                </td>

                <td class="{{ $class }}">
                    {{ number_format($value, 2) }}
                </td>

                <td class="{{ $class }}">
                    {{ number_format($percentage, 2) }}%
                </td>
            </tr>

        @empty

            <tr>
                <td colspan="4" class="empty-row">
                    No emission source data available.
                </td>
            </tr>

        @endforelse

        {{-- TOTAL EMISSIONS ROW --}}

        <tr class="grand-total-row">

            <td colspan="2">
                TOTAL EMISSIONS
            </td>

            <td>
                {{ number_format($totalEmissions, 2) }}
            </td>

            <td>
                100%
            </td>

        </tr>

    </tbody>

</table>


<div class="footer">
    CarbonWise Sustainability Monitoring System
</div>


{{-- =================================================
     PAGE 2 ONWARD: DETAILED RECORDS
================================================= --}}

<div class="page-break"></div>

<h2 class="section-title">
    Detailed Carbon Emission Records
</h2>

<table class="records-table">

    <thead>
        <tr>
            <th class="user-column">User Email</th>
            <th class="number-column">Transportation</th>
            <th class="number-column">Electricity</th>
            <th class="number-column">Food</th>
            <th class="total-column">Total Emission (kg CO₂e)</th>
            <th class="date-column">Date</th>
        </tr>
    </thead>

    <tbody>

        @forelse($records as $record)

            @php
                $rowSourceTotal =
                    (float) $record->transportation +
                    (float) $record->electricity +
                    (float) $record->food;

                $userEmail = $record->user_email
                    ?? optional($record->user)->email
                    ?? 'Unknown User';
            @endphp

            <tr>

                <td class="user-column">
                    {{ $userEmail }}
                </td>

                <td class="{{ $getEmissionClass((float) $record->transportation, $rowSourceTotal) }}">
                    {{ number_format((float) $record->transportation, 2) }}
                </td>

                <td class="{{ $getEmissionClass((float) $record->electricity, $rowSourceTotal) }}">
                    {{ number_format((float) $record->electricity, 2) }}
                </td>

                <td class="{{ $getEmissionClass((float) $record->food, $rowSourceTotal) }}">
                    {{ number_format((float) $record->food, 2) }}
                </td>

                <td class="total-column {{ $getTotalEmissionClass(
                    (float) $record->total_emission,
                    $averageEmissionPerRecord
                ) }}">
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
                <td colspan="6" class="empty-row">
                    No carbon records found for this report.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>


{{-- =================================================
     EMISSION ANALYSIS AND INTERPRETATION
================================================= --}}

<div class="page-break"></div>

<h2 class="section-title">
    Emission Analysis and Interpretation
</h2>


<div class="interpretation-box">

    <h3>1. Overall Emission Interpretation</h3>

    <p>
        {{ $totalInterpretation }}
    </p>

    <p>
        The total emissions represent the sum of the
        total_emission values from the exported carbon records.
        The average represents the total emissions divided by
        the number of exported records.
    </p>

</div>


<div class="interpretation-box">

    <h3>2. Emission Source Analysis</h3>

    <p>
        {{ $interpretation }}
    </p>

    <p>
        The source rankings identify which activities contribute
        the largest portions of the recorded emissions. This
        information can help determine which categories should
        receive priority when planning emission-reduction initiatives.
    </p>

</div>


<div class="interpretation-box">

    <h3>3. Source Comparison</h3>

    <p>
        {{ $sourceComparisonInterpretation }}
    </p>

    <p>
        A higher contribution percentage indicates that a category
        accounts for a larger share of the combined emissions from
        the three monitored sources.
    </p>

</div>


<div class="interpretation-box">

    <h3>4. Total Emissions and Source Data Comparison</h3>

    <p>
        The total from the total_emission column is
        {{ number_format($totalEmissions, 2) }} kg CO₂e.
        Meanwhile, the combined total of Transportation,
        Electricity, and Food is
        {{ number_format($combinedSourceTotal, 2) }} kg CO₂e.
    </p>

    <p>
        The difference between these values is
        <strong>
            {{ number_format(abs($emissionDifference), 2) }} kg CO₂e.
        </strong>
    </p>

    <p>
        This comparison helps verify whether the total emission
        values are consistent with the three source categories.
        Any difference should be checked against the system's
        emission calculation logic.
    </p>

</div>


<div class="recommendation-box">

    <h3>Suggested Mitigation Action</h3>

    <p>
        Based on the source analysis, the following action
        is recommended for the leading emission category:
    </p>

    <p style="margin-top: 7px;">
        {{ $recommendation }}
    </p>

</div>


<div class="footer">
    CarbonWise Sustainability Monitoring System
</div>

</body>
</html>