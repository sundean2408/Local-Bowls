<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nama' => 'Admin Utama',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        User::create([
            'nama' => 'Budi Waiter',
            'username' => 'waiter',
            'password' => Hash::make('password123'),
            'role' => 'waiter',
        ]);

        User::create([
            'nama' => 'Sari Kitchen',
            'username' => 'kitchen',
            'password' => Hash::make('password123'),
            'role' => 'kitchen',
        ]);

        User::create([
            'nama' => 'Andi Kasir',
            'username' => 'kasir',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
        ]);
    }
}