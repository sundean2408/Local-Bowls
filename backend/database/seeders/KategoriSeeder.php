<?php
namespace Database\Seeders;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        Kategori::insert([
            ['nama_kategori' => 'Mie Nusantara'],
            ['nama_kategori' => 'Minuman Dingin'],
            ['nama_kategori' => 'Minuman Panas'],
            ['nama_kategori' => 'Cemilan'],
        ]);
    }
}