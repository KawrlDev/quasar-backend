<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WebsiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('website_settings')->truncate(); // clears all rows
        DB::table('website_settings')->insert([
            'id' => 1,
            'eligibility_cooldown' => 90,
        ]);
    }
}
