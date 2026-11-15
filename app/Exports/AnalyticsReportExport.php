<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class AnalyticsReportExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithCustomChunkSize,
    WithColumnWidths,
    WithEvents
{
    /**
     * Export the latest 10,000 carbon records.
     */
    public function query(): Builder
    {
        return DB::table('carbon_records')
            ->leftJoin(
                'users',
                'carbon_records.user_id',
                '=',
                'users.id'
            )
            ->select([
                'users.email as user_email',
                'carbon_records.transportation',
                'carbon_records.electricity',
                'carbon_records.food',
                'carbon_records.total_emission',
                'carbon_records.record_date',
            ])
            ->orderByDesc('carbon_records.record_date')
            ->orderByDesc('carbon_records.id')
            ->limit(10000);
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        return [
            'User Email',
            'Transportation',
            'Electricity',
            'Food',
            'Total Emission (kg CO2e)',
            'Record Date',
        ];
    }

    /**
     * Map each database record to an Excel row.
     */
    public function map($row): array
    {
        return [
            $row->user_email ?? 'Unknown User',
            (float) ($row->transportation ?? 0),
            (float) ($row->electricity ?? 0),
            (float) ($row->food ?? 0),
            (float) ($row->total_emission ?? 0),
            $row->record_date ?? '',
        ];
    }

    /**
     * Process records in chunks of 500.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Set fixed Excel column widths.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 20,
            'C' => 20,
            'D' => 18,
            'E' => 25,
            'F' => 18,
        ];
    }

    /**
     * Add source colors, total-emission colors, rankings,
     * report interpretation, recommendation, and legend.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /*
                 * Retrieve the same latest 10,000 record IDs
                 * included in the Excel export.
                 */
                $idsQuery = DB::table('carbon_records')
                    ->select('id')
                    ->orderByDesc('record_date')
                    ->orderByDesc('id')
                    ->limit(10000);

                /*
                 * Calculate summary values from those same records.
                 */
                $summary = DB::table('carbon_records')
                    ->whereIn('id', clone $idsQuery)
                    ->selectRaw('
                        COUNT(*) as record_count,
                        COALESCE(SUM(transportation), 0) as transportation,
                        COALESCE(SUM(electricity), 0) as electricity,
                        COALESCE(SUM(food), 0) as food,
                        COALESCE(SUM(total_emission), 0) as total_emission
                    ')
                    ->first();

                $recordCount = (int) ($summary->record_count ?? 0);
                $lastDataRow = $recordCount + 1;

                $averageTotalEmission = $recordCount > 0
                    ? ((float) $summary->total_emission / $recordCount)
                    : 0;

                /*
                 * Color source values according to their percentage
                 * of the individual record's combined source emissions.
                 *
                 * Red:    50% or higher
                 * Orange: 25% to below 50%
                 * Green:  Above 0% to below 25%
                 * Gray:   Zero contribution
                 */
                for ($row = 2; $row <= $lastDataRow; $row++) {

                    $transportation = (float) $sheet
                        ->getCell("B{$row}")
                        ->getValue();

                    $electricity = (float) $sheet
                        ->getCell("C{$row}")
                        ->getValue();

                    $food = (float) $sheet
                        ->getCell("D{$row}")
                        ->getValue();

                    $rowSourceTotal =
                        $transportation +
                        $electricity +
                        $food;

                    $sourceValues = [
                        'B' => $transportation,
                        'C' => $electricity,
                        'D' => $food,
                    ];

                    foreach ($sourceValues as $column => $value) {

                        if ($value <= 0 || $rowSourceTotal <= 0) {
                            $color = 'FF808080';
                        } else {

                            $percentage = ($value / $rowSourceTotal) * 100;

                            if ($percentage >= 50) {
                                $color = 'FFFF0000';
                            } elseif ($percentage >= 25) {
                                $color = 'FFFFA500';
                            } else {
                                $color = 'FF008000';
                            }
                        }

                        $sheet->getStyle("{$column}{$row}")
                            ->getFont()
                            ->getColor()
                            ->setARGB($color);
                    }

                    /*
                     * Color Total Emission relative to the average
                     * total emission per exported record.
                     *
                     * Red:    150% or more of average
                     * Orange: 100% to below 150% of average
                     * Green:  Above zero but below average
                     * Gray:   Zero emission
                     */
                    $totalEmission = (float) $sheet
                        ->getCell("E{$row}")
                        ->getValue();

                    if ($totalEmission <= 0) {
                        $totalColor = 'FF808080';
                    } elseif ($averageTotalEmission <= 0) {
                        $totalColor = 'FF008000';
                    } else {

                        $ratio = $totalEmission / $averageTotalEmission;

                        if ($ratio >= 1.5) {
                            $totalColor = 'FFFF0000';
                        } elseif ($ratio >= 1.0) {
                            $totalColor = 'FFFFA500';
                        } else {
                            $totalColor = 'FF008000';
                        }
                    }

                    $sheet->getStyle("E{$row}")
                        ->getFont()
                        ->getColor()
                        ->setARGB($totalColor);
                }

                /*
                 * Prepare source totals and percentages.
                 */
                $sources = [
                    'Transportation' => (float) $summary->transportation,
                    'Electricity' => (float) $summary->electricity,
                    'Food' => (float) $summary->food,
                ];

                $combinedSourceTotal = array_sum($sources);
                $totalEmissions = (float) $summary->total_emission;

                /*
                 * Rank sources from highest to lowest.
                 */
                arsort($sources);

                $highestSource = $combinedSourceTotal > 0
                    ? array_key_first($sources)
                    : null;

                $highestPercentage = $highestSource !== null
                    ? ($sources[$highestSource] / $combinedSourceTotal) * 100
                    : 0;

                /*
                 * Place the summary directly below exported records.
                 */
                $startRow = $lastDataRow + 3;

                $sheet->setCellValue(
                    "A{$startRow}",
                    'CARBON EMISSION SUMMARY AND INTERPRETATION'
                );

                $sheet->mergeCells("A{$startRow}:F{$startRow}");

                $sheet->getStyle("A{$startRow}")
                    ->getFont()
                    ->setBold(true)
                    ->setSize(14);

                /*
                 * Overall report metrics.
                 */
                $infoRow = $startRow + 1;

                $sheet->setCellValue("A{$infoRow}", 'Total Records');
                $sheet->setCellValue("B{$infoRow}", $recordCount);
                $sheet->setCellValue("C{$infoRow}", 'Total Emissions');
                $sheet->setCellValue("D{$infoRow}", $totalEmissions);
                $sheet->setCellValue("E{$infoRow}", 'kg CO2e');

                $sheet->getStyle("A{$infoRow}:E{$infoRow}")
                    ->getFont()
                    ->setBold(true);

                /*
                 * Source ranking table.
                 */
                $headerRow = $startRow + 3;

                $sheet->setCellValue("A{$headerRow}", 'Rank');
                $sheet->setCellValue("B{$headerRow}", 'Emission Source');
                $sheet->setCellValue("C{$headerRow}", 'Total Emissions (kg CO2e)');
                $sheet->setCellValue("D{$headerRow}", 'Contribution');

                $sheet->getStyle("A{$headerRow}:D{$headerRow}")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("A{$headerRow}:D{$headerRow}")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FF166534');

                $sheet->getStyle("A{$headerRow}:D{$headerRow}")
                    ->getFont()
                    ->getColor()
                    ->setARGB('FFFFFFFF');

                $summaryRow = $headerRow + 1;
                $rank = 1;

                foreach ($sources as $source => $value) {

                    $percentage = $combinedSourceTotal > 0
                        ? ($value / $combinedSourceTotal) * 100
                        : 0;

                    $sheet->setCellValue("A{$summaryRow}", $rank);
                    $sheet->setCellValue("B{$summaryRow}", $source);
                    $sheet->setCellValue("C{$summaryRow}", $value);
                    $sheet->setCellValue(
                        "D{$summaryRow}",
                        number_format($percentage, 2) . '%'
                    );

                    if ($value <= 0 || $combinedSourceTotal <= 0) {
                        $color = 'FF808080';
                    } elseif ($percentage >= 50) {
                        $color = 'FFFF0000';
                    } elseif ($percentage >= 25) {
                        $color = 'FFFFA500';
                    } else {
                        $color = 'FF008000';
                    }

                    $sheet->getStyle("C{$summaryRow}:D{$summaryRow}")
                        ->getFont()
                        ->getColor()
                        ->setARGB($color);

                    $summaryRow++;
                    $rank++;
                }

                /*
                 * Add total emissions inside the ranking table.
                 */
                $sheet->setCellValue("A{$summaryRow}", 'TOTAL EMISSIONS');
                $sheet->mergeCells("A{$summaryRow}:B{$summaryRow}");
                $sheet->setCellValue("C{$summaryRow}", $totalEmissions);
                $sheet->setCellValue("D{$summaryRow}", '100%');

                $sheet->getStyle("A{$summaryRow}:D{$summaryRow}")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("A{$summaryRow}:D{$summaryRow}")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFECFDF5');

                $sheet->getStyle("A{$summaryRow}:D{$summaryRow}")
                    ->getFont()
                    ->getColor()
                    ->setARGB('FF166534');

                /*
                 * Overall interpretation.
                 */
                $interpretationRow = $summaryRow + 2;

                if ($recordCount > 0) {
                    $average = $totalEmissions / $recordCount;

                    $interpretation =
                        "The {$recordCount} exported carbon records contain " .
                        number_format($totalEmissions, 2) .
                        " kg CO2e in total emissions, with an average of " .
                        number_format($average, 2) .
                        " kg CO2e per record. ";

                    if ($highestSource !== null) {
                        $interpretation .=
                            "{$highestSource} is the highest contributing " .
                            "source, accounting for " .
                            number_format($highestPercentage, 2) .
                            "% of the combined emissions from Transportation, " .
                            "Electricity, and Food.";
                    } else {
                        $interpretation .=
                            "No source emissions are available for comparison.";
                    }
                } else {
                    $interpretation =
                        'No carbon records are available for interpretation.';
                }

                $sheet->setCellValue(
                    "A{$interpretationRow}",
                    'Overall Interpretation'
                );

                $sheet->setCellValue(
                    "B{$interpretationRow}",
                    $interpretation
                );

                $sheet->mergeCells(
                    "B{$interpretationRow}:F" . ($interpretationRow + 2)
                );

                $sheet->getStyle("A{$interpretationRow}")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("B{$interpretationRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('center');

                $sheet->getRowDimension($interpretationRow)
                    ->setRowHeight(65);

                /*
                 * Suggested mitigation action for the leading source.
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
                    : 'No recommendation can be generated because no source emission data is available.';

                $recommendationRow = $interpretationRow + 4;

                $sheet->setCellValue(
                    "A{$recommendationRow}",
                    'Suggested Mitigation Action'
                );

                $sheet->setCellValue(
                    "B{$recommendationRow}",
                    $recommendation
                );

                $sheet->mergeCells(
                    "B{$recommendationRow}:F" . ($recommendationRow + 2)
                );

                $sheet->getStyle("A{$recommendationRow}")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("B{$recommendationRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('center');

                $sheet->getRowDimension($recommendationRow)
                    ->setRowHeight(55);

                /*
                 * Add a horizontal color legend.
                 */
                $legendRow = $recommendationRow + 4;

                $sheet->setCellValue("A{$legendRow}", 'COLOR LEGEND');
                $sheet->setCellValue("B{$legendRow}", 'Red: High');
                $sheet->setCellValue("C{$legendRow}", 'Orange: Moderate');
                $sheet->setCellValue("D{$legendRow}", 'Green: Low');
                $sheet->setCellValue("E{$legendRow}", 'Gray: Zero');

                $sheet->getStyle("A{$legendRow}")
                    ->getFont()
                    ->setBold(true);

                $legendColors = [
                    'B' => 'FFFF0000',
                    'C' => 'FFFFA500',
                    'D' => 'FF008000',
                    'E' => 'FF808080',
                ];

                foreach ($legendColors as $column => $color) {
                    $sheet->getStyle("{$column}{$legendRow}")
                        ->getFont()
                        ->getColor()
                        ->setARGB($color);
                }

                $noteRow = $legendRow + 2;

                $sheet->setCellValue(
                    "A{$noteRow}",
                    'Source columns use each record’s percentage of its combined source emissions: red 50% or higher, orange 25% to below 50%, green above 0% to below 25%, and gray zero. Total Emission uses the average per exported record: red 150% or more of average, orange average to below 150%, green above zero but below average, and gray zero. These are relative indicators, not official environmental standards.'
                );

                $sheet->mergeCells("A{$noteRow}:F{$noteRow}");

                $sheet->getStyle("A{$noteRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('center');

                $sheet->getRowDimension($noteRow)
                    ->setRowHeight(45);

                /*
                 * Apply general formatting to the summary section.
                 */
                $sheet->getStyle("A{$startRow}:F{$noteRow}")
                    ->getAlignment()
                    ->setVertical('center')
                    ->setWrapText(true);

                $sheet->getStyle('A1:F1')
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
