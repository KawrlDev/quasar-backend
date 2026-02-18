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
            ['sector' => 'SENIOR', 'is_active' => true],
            ['sector' => 'PWD', 'is_active' => true],
            ['sector' => 'SOLO PARENT', 'is_active' => true],
        ]);
    }
}
