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
            'password' => Hash::make('Ulises,2024'), // Usar Hash para la contraseña
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'name' => 'Cecilia',
            'email' => 'lisrp.97@gmail.com',
            'password' => Hash::make('Cecilia2024'), // Usar Hash para la contraseña
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('users')->insert([
            'name' => 'Ulises',
            'email' => 'deyanirap862@gmail.com',
            'password' => Hash::make('Deyanira2024'), // Usar Hash para la contraseña
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // DB::table('users')->insert([
        //     'name' => 'Luis',
        //     'email' => '
    } //

}
