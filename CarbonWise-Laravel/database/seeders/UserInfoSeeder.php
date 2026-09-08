<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class UserInfoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $campuses = [
            'Pablo Borbon Campus',
            'Alangilan Campus',
            'Lipa Campus',
            'Nasugbu Campus',
            'Lemery Campus',
        ];

        $departments = [

            'College of Informatics and Computing Sciences',
            'College of Arts and Sciences',
            'College of Engineering',
            'College of Industrial Technology',
            'College of Teacher Education',
            'College of Accountancy',
            'Library Services',
            'Registrar',
            'SDO Office'

        ];

        for ($i = 1; $i <= 10000; $i++) {

            $studentNumber = '23-' . str_pad($i, 5, '0', STR_PAD_LEFT);

            $email = strtolower($studentNumber) . '@g.batstate-u.edu.ph';

            $role = $faker->randomElement([
                'student',
                'student',
                'student',
                'student',
                'faculty',
                'admin'
            ]);

            DB::table('user_info')->insert([

                'sr_code' => $studentNumber,

                'full_name' => $faker->name(),

                'g_suite' => $email,

                'password' => '12345678',

                'campus' => $faker->randomElement($campuses),

                'year_level' => $role == 'student'
                    ? rand(1,4)
                    : null,

                'department' => $faker->randomElement($departments),

                'role' => $role,

                'status' => 'Active',

                'faculty_type' => $role == 'faculty'
                    ? $faker->randomElement([
                        'Teaching Faculty',
                        'Administrative Faculty'
                    ])
                    : null,

                'office' => $role == 'admin'
                    ? $faker->randomElement([
                        'Registrar',
                        'Accounting',
                        'SDO Office'
                    ])
                    : null,

                'home_address' => $faker->address(),

                'latitude' => $faker->latitude(13.6,13.9),

                'longitude' => $faker->longitude(121.0,121.2),

            ]);

        }
    }
}