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
            ['preference' => 'N/A'],
            ['preference' => 'GAY'],
            ['preference' => 'LESBIAN'],
        ]);
    }
}
