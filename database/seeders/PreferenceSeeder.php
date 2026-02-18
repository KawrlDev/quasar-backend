<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PreferenceSeeder extends Seeder
{
    public function run()
    {
        DB::table('preferences')->insert([
            ['preference' => 'N/A', 'is_active' => true],
            ['preference' => 'GAY', 'is_active' => true],
            ['preference' => 'LESBIAN', 'is_active' => true],
        ]);
    }
}
