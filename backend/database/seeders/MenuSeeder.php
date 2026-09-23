<?php
namespace Database\Seeders;
use App\Models\Menu;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $mie = Kategori::where('nama_kategori', 'Mie Nusantara')->first()->id;
        $dingin = Kategori::where('nama_kategori', 'Minuman Dingin')->first()->id;
        $panas = Kategori::where('nama_kategori', 'Minuman Panas')->first()->id;
        $cemilan = Kategori::where('nama_kategori', 'Cemilan')->first()->id;

        Menu::insert([
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Aceh', 'harga' => 30000, 'gambar' => 'images/menu/Mie-Aceh.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Cakalang', 'harga' => 29000, 'gambar' => 'images/menu/Mie-Cakalang.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Celor Palembang', 'harga' => 28000, 'gambar' => 'images/menu/Mie-Celor-Palembang.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Koba', 'harga' => 27000, 'gambar' => 'images/menu/Mie-Koba.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Kocok Bandung', 'harga' => 27000, 'gambar' => 'images/menu/Mie-Kocok-Bandung.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Kopyok', 'harga' => 25000, 'gambar' => 'images/menu/Mie-Kopyok.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Ongklok', 'harga' => 26000, 'gambar' => 'images/menu/mie-ongklok.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Rebus Godog Jawa', 'harga' => 25000, 'gambar' => 'images/menu/Mie-rebus-godog-jawa.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Tiaw', 'harga' => 28000, 'gambar' => 'images/menu/Mie-Tiaw.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Soto Mie Bogor', 'harga' => 27000, 'gambar' => 'images/menu/Soto-Mie-Bogor.jpg'],

            ['id_kategori' => $dingin, 'nama_menu' => 'Es Dawet', 'harga' => 12000, 'gambar' => 'images/menu/es-dawet.jpg'],
            ['id_kategori' => $dingin, 'nama_menu' => 'Es Goyobod', 'harga' => 13000, 'gambar' => 'images/menu/es-goyobod.jpg'],
            ['id_kategori' => $dingin, 'nama_menu' => 'Es Lidah Buaya', 'harga' => 11000, 'gambar' => 'images/menu/es-lidah-buaya.jpg'],
            ['id_kategori' => $dingin, 'nama_menu' => 'Es Markisa', 'harga' => 12000, 'gambar' => 'images/menu/es-markisa.jpg'],
            ['id_kategori' => $dingin, 'nama_menu' => 'Es Pisang Hijau', 'harga' => 14000, 'gambar' => 'images/menu/Es-Pisang-Hiijau.jpg'],

            ['id_kategori' => $panas, 'nama_menu' => 'Bajigur', 'harga' => 10000, 'gambar' => 'images/menu/bajigur.jpg'],
            ['id_kategori' => $panas, 'nama_menu' => 'Kunyit Asem', 'harga' => 10000, 'gambar' => 'images/menu/kunyit-asem.jpg'],
            ['id_kategori' => $panas, 'nama_menu' => 'Sarabba', 'harga' => 11000, 'gambar' => 'images/menu/sarabba.jpg'],
            ['id_kategori' => $panas, 'nama_menu' => 'Sekoteng', 'harga' => 10000, 'gambar' => 'images/menu/sekoteng.jpg'],
            ['id_kategori' => $panas, 'nama_menu' => 'Wedang Jahe', 'harga' => 9000, 'gambar' => 'images/menu/wedang-jahe.jpg'],

            ['id_kategori' => $cemilan, 'nama_menu' => 'Batagor Bandung', 'harga' => 18000, 'gambar' => 'images/menu/Batagor-Bandung.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Burayot Garut', 'harga' => 12000, 'gambar' => 'images/menu/Burayot-Garut.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Klepon Jawa', 'harga' => 10000, 'gambar' => 'images/menu/Klepon-Jawa.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Lumpia Tape Cokelat Jember', 'harga' => 13000, 'gambar' => 'images/menu/Lumpia-Tape-Cokelat-Jember.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Otak-Otak Bakar Jakarta', 'harga' => 20000, 'gambar' => 'images/menu/Otak-Otak-Bakar-Jakarta.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Pempek Palembang', 'harga' => 22000, 'gambar' => 'images/menu/Pempek-Palembang.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Pisang Epe Makassar', 'harga' => 15000, 'gambar' => 'images/menu/Pisang-Epe-Makassar.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Serabi Bandung', 'harga' => 12000, 'gambar' => 'images/menu/Serabi-Bandung.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Tahu Walik Banyuwangi', 'harga' => 14000, 'gambar' => 'images/menu/Tahu-Walik-Banyuwangi.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Tempe Mendoan Purwokerto', 'harga' => 12000, 'gambar' => 'images/menu/Tempe-Mendoan-Purwokerto.jpg'],
        ]);
    }
}