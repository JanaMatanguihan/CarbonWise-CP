<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CarbonRecordsSeeder extends Seeder
{
    public function run(): void
    {
        $users = DB::table('user_info')
            ->select('g_suite', 'role', 'department')
            ->get();

        $records = [];
        $batchSize = 1000;

        foreach ($users as $user) {

           // Generate daily records for 2026
                for ($year = 2021; $year <= 2026; $year++) {

                    $startDate = Carbon::create($year, 1, 1);

                    $endDate = Carbon::create($year, 12, 31);

                    for (
                        $recordDate = $startDate->copy();
                        $recordDate->lte($endDate);
                        $recordDate->addDay()
                    ) {


                    // Transportation based on role
                    $transportation = match ($user->role) {
                        'student' => rand(120, 260) / 100,
                        'faculty' => rand(180, 340) / 100,
                        'admin' => rand(150, 300) / 100,
                        default => rand(120, 260) / 100,
                    };

                    // Electricity
                    $electricity = rand(180, 350) / 100;

                    // Summer months (March-May)
                    if (in_array($recordDate->month, [3, 4, 5])) {
                        $electricity += rand(20, 60) / 100;
                    }

                    // Food
                    $food = rand(220, 500) / 100;

                    // Continue annual growth from previous years
                    $growthMultiplier = 1 + (($recordDate->year - 2021) * 0.06);

                    $transportation *= $growthMultiplier;
                    $electricity *= $growthMultiplier;
                    $food *= $growthMultiplier;

                    // Small random fluctuation
                    $transportation += rand(-10, 10) / 100;
                    $electricity += rand(-10, 10) / 100;
                    $food += rand(-15, 15) / 100;

                    // Prevent negative values
                    $transportation = max(0, round($transportation, 2));
                    $electricity = max(0, round($electricity, 2));
                    $food = max(0, round($food, 2));

                    $total = round(
                        $transportation +
                        $electricity +
                        $food,
                        2
                    );

                    $records[] = [

                        'g_suite' => $user->g_suite,

                        'transportation' => $transportation,

                        'electricity' => $electricity,

                        'food' => $food,

                        'total_emission' => $total,

                        'record_date' => $recordDate->format('Y-m-d'),

                        'created_at' => $recordDate->copy()->addHours(rand(7, 18)),

                        'updated_at' => $recordDate->copy()->addHours(rand(7, 18)),

                        'ai_recommendation' => null,

                    ];

                    if (count($records) >= $batchSize) {
                        DB::table('carbon_records')->insert($records);
                        $records = [];
                    }
                }
            }
        }

        if (!empty($records)) {
            DB::table('carbon_records')->insert($records);
        }

        $this->command->info('2026 carbon records generated successfully!');
    }
}