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
            ['category' => 'MEDICINE', 'partner' => 'PHARMACITI'],
            ['category' => 'MEDICINE', 'partner' => 'QURESS'],
            ['category' => 'LABORATORY', 'partner' => 'PERPETUAL LAB'],
            ['category' => 'LABORATORY', 'partner' => 'MEDILIFE'],
            ['category' => 'LABORATORY', 'partner' => 'LEXAS'],
            ['category' => 'LABORATORY', 'partner' => 'CITY MED'],
            ['category' => 'HOSPITAL', 'partner' => 'TAGUM GLOBAL'],
            ['category' => 'HOSPITAL', 'partner' => 'CHRIST THE KING'],
            ['category' => 'HOSPITAL', 'partner' => 'MEDICAL MISSION'],
            ['category' => 'HOSPITAL', 'partner' => 'TMC'],
        ]);
    }
}
