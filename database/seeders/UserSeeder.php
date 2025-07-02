<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate(); // Optional: Clears existing users


        // User::create([
        //     'name' => 'Heavy Object Goup',
        //     'email' => 'heavyobject@example.com',
        //     'password' => Hash::make('heavyobject'),
        //     'role' => 'admin',
        // ]);
        User::create([
            'name' => 'Heavy Cell User',
            'email' => 'heavycell@example.com',
            'password' => Hash::make('heavycell123'),
            'role' => 'admin',
        ]);

        User::create(attributes: [
            'name' => 'Sticky Up User',
            'email' => 'stickyup@example.com',
            'password' => Hash::make('stickyup123'),
            'role' => 'user',
        ]);

        User::create([
            'name' => 'Dr. Funding User',
            'email' => 'drfunding@example.com',
            'password' => Hash::make('drfunding123'),
            'role' => 'user',
        ]);
        
    }
}