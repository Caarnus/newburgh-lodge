<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrgEventTypesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Stated Meeting', 'category' => 'STATED',  'color' => '#449fe3'],
            ['name' => 'Called Meeting', 'category' => 'DEGREE',  'color' => '#6a3499'],
            ['name' => 'Fundraiser',     'category' => 'FUND',    'color' => '#0ca34f'],
            ['name' => 'Executive Committee Meeting', 'category' => 'EXEC', 'color' => '#e81c2b'],
            ['name' => 'Special Event',  'category' => 'SPECIAL', 'color' => '#eace2a'],
            ['name' => 'Other Lodge',    'category' => 'OTHER',   'color' => '#f4891e'],
        ];

        foreach ($rows as $row) {
            DB::table('org_event_types')->updateOrInsert(
                ['category' => $row['category']],
                $row
            );
        }
    }
}
