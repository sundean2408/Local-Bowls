<?php

namespace Database\Seeders;

use App\Models\Meja;
use Illuminate\Database\Seeder;

class MejaSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Meja::firstOrCreate(
                ['nomor_meja' => (string) $i],
                ['qr_code' => 'table=' . $i, 'status_meja' => 'kosong'],
            );
        }
    }
}