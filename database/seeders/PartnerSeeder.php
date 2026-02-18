<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PartnerSeeder extends Seeder
{
    public function run()
    {
        DB::table('partners')->insert([
            ['category' => 'MEDICINE', 'partner' => 'PHARMACITI', 'is_active' => true],
            ['category' => 'MEDICINE', 'partner' => 'QURESS', 'is_active' => true],
            ['category' => 'LABORATORY', 'partner' => 'PERPETUAL LAB', 'is_active' => true],
            ['category' => 'LABORATORY', 'partner' => 'MEDILIFE', 'is_active' => true],
            ['category' => 'LABORATORY', 'partner' => 'LEXAS', 'is_active' => true],
            ['category' => 'LABORATORY', 'partner' => 'CITY MED', 'is_active' => true],
            ['category' => 'HOSPITAL', 'partner' => 'TAGUM GLOBAL', 'is_active' => true],
            ['category' => 'HOSPITAL', 'partner' => 'CHRIST THE KING', 'is_active' => true],
            ['category' => 'HOSPITAL', 'partner' => 'MEDICAL MISSION', 'is_active' => true],
            ['category' => 'HOSPITAL', 'partner' => 'TMC', 'is_active' => true],
        ]);
    }
}
