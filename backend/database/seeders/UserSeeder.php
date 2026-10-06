<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $username = trim((string) config('app.bootstrap_admin.username'));
            $password = (string) config('app.bootstrap_admin.password');

            if ($username === '' || strlen($password) < 16) {
                throw new \RuntimeException(
                    'Set ADMIN_USERNAME and ADMIN_PASSWORD (at least 16 characters) before seeding production users.',
                );
            }

            $existingUser = User::where('username', $username)->first();
            if ($existingUser && $existingUser->role !== 'admin') {
                throw new \RuntimeException('ADMIN_USERNAME is already assigned to a non-admin user.');
            }

            User::firstOrCreate(
                ['username' => $username],
                [
                    'nama' => config('app.bootstrap_admin.name', 'Admin Utama'),
                    'password' => Hash::make($password),
                    'role' => 'admin',
                ],
            );

            return;
        }

        $accounts = [
            ['nama' => 'Admin Utama', 'username' => 'admin', 'password' => 'admin123', 'role' => 'admin'],
            ['nama' => 'Sari Kitchen', 'username' => 'kitchen', 'password' => 'kitchen123', 'role' => 'kitchen'],
            ['nama' => 'Andi Kasir', 'username' => 'kasir', 'password' => 'kasir123', 'role' => 'kasir'],
        ];

        foreach ($accounts as $account) {
            User::firstOrCreate(
                ['username' => $account['username']],
                [
                    'nama' => $account['nama'],
                    'password' => Hash::make($account['password']),
                    'role' => $account['role'],
                ],
            );
        }
    }
}
