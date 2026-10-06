<?php
namespace Database\Seeders;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Mie Nusantara', 'Minuman Dingin', 'Minuman Panas', 'Cemilan'] as $namaKategori) {
            Kategori::firstOrCreate(['nama_kategori' => $namaKategori]);
        }
    }
}