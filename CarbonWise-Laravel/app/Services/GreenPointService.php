<?php

namespace App\Services;

use App\Models\CarbonRecord;

class GreenPointService
{
    public function calculate(string $gSuite): int
    {
        $records = CarbonRecord::where('g_suite', $gSuite)->get();

        $points = 0;

        foreach ($records as $record) {

            $emission = $record->total_emission;

            if ($emission <= 5) {

                $points += 100;

            } elseif ($emission <= 10) {

                $points += 80;

            } elseif ($emission <= 15) {

                $points += 60;

            } elseif ($emission <= 20) {

                $points += 40;

            } else {

                $points += 20;

            }
        }

        return $points;
    }
}