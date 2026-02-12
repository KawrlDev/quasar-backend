<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'USERNAME' => 'ADMIN',
            'PASSWORD' => '$2y$12$JdibzUcHFkBJSqXX0IXFmet5TAcWyGnDveKedqLla19CnPrnj/4mK',
            'REMEMBER_TOKEN' => 'cq2DztGN9gqIoZFV3VUnEEjOKMHlgbIn2I8YeOy7pcLeiHcWvKrNOcl4M54Q',
            'ROLE' => 'ADMIN'
        ]);
    }
}
