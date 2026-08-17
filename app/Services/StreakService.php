<?php

namespace App\Services;

use App\Models\CarbonRecord;

class StreakService
{
    public function calculate(string $gSuite): int
    {
        $dates = CarbonRecord::where('g_suite', $gSuite)
        ->whereDate('record_date', '<=', now())
        ->orderBy('record_date', 'desc')
        ->pluck('record_date')
        ->unique()
        ->values();

       if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 1;

        for ($i = 0; $i < $dates->count() - 1; $i++) {

            $current = \Carbon\Carbon::parse($dates[$i]);

            $previous = \Carbon\Carbon::parse($dates[$i + 1]);

            if ($current->diffInDays($previous) == 1) {

                $streak++;

            } else {

                break;

            }
        }

        return $streak;
    }

    public function getCurrentWeekActivity(string $gSuite): array
    {
        $records = CarbonRecord::where('g_suite', $gSuite)
            ->whereBetween('record_date', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
            ->orderBy('record_date')
            ->get();

            $weekActivity = [
            'Mon' => false,
            'Tue' => false,
            'Wed' => false,
            'Thu' => false,
            'Fri' => false,
            'Sat' => false,
            'Sun' => false,
        ];

        foreach ($records as $record) {

            $day = \Carbon\Carbon::parse($record->record_date)->format('D');

            if (array_key_exists($day, $weekActivity)) {
                $weekActivity[$day] = true;
            }

        }

        return $weekActivity;
    }
}