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
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        User::create([
            'nama' => 'Sari Kitchen',
            'username' => 'kitchen',
            'password' => Hash::make('kitchen123'),
            'role' => 'kitchen',
        ]);

        User::create([
            'nama' => 'Andi Kasir',
            'username' => 'kasir',
            'password' => Hash::make('kasir123'),
            'role' => 'kasir',
        ]);
    }
}
