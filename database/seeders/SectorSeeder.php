<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SectorSeeder extends Seeder
{
    public function run()
    {
        DB::table('sectors')->insert([
            ['sector' => 'SENIOR'],
            ['sector' => 'PWD'],
            ['sector' => 'SOLO PARENT'],
        ]);
    }
}
