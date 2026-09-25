<?php

namespace App\Services;

use App\Models\User;
use App\Models\CarbonRecord;

class GreenPointService
{
    public function calculate(string $gSuite): int
    {
        $user = User::where('email', $gSuite)->first();

        if (!$user) {
            return 0;
        }

        $records = CarbonRecord::where('user_id', $user->id)->get();

        $points = 0;

        foreach ($records as $record) {

            $emission = (float) $record->total_emission;

            if ($emission <= 5) {
                $points += 10;
            } elseif ($emission <= 10) {
                $points += 5;
            } elseif ($emission <= 20) {
                $points += 2;
            }
        }

        return $points;
    }
}