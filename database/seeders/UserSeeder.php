<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Ulises',
            'email' => 'uli.rp1999@gmail.com',
            'password' => Hash::make('12345678'), // Usar Hash para la contraseña
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // DB::table('users')->insert([
        //     'name' => 'Luis',
        //     'email' => '
    } //

}
