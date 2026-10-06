<?php
namespace Database\Seeders;
use App\Models\Menu;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $mie = Kategori::firstOrCreate(['nama_kategori' => 'Mie Nusantara'])->id;
        $dingin = Kategori::firstOrCreate(['nama_kategori' => 'Minuman Dingin'])->id;
        $panas = Kategori::firstOrCreate(['nama_kategori' => 'Minuman Panas'])->id;
        $cemilan = Kategori::firstOrCreate(['nama_kategori' => 'Cemilan'])->id;

        $menus = [
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Aceh', 'deskripsi' => 'Mie tebal berbumbu kari pedas khas Aceh dengan udang dan daging sapi, disajikan dengan emping dan jeruk nipis.', 'harga' => 30000, 'gambar' => 'images/menu/Mie-Aceh.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Cakalang', 'deskripsi' => 'Mie khas Manado dengan suwiran ikan cakalang asap gurih, sayuran segar, dan sambal roa yang pedas nendang.', 'harga' => 29000, 'gambar' => 'images/menu/Mie-Cakalang.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Celor Palembang', 'deskripsi' => 'Mie lembut disiram kuah santan udang yang kental gurih, dilengkapi telur rebus dan tauge segar.', 'harga' => 28000, 'gambar' => 'images/menu/Mie-Celor-Palembang.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Koba', 'deskripsi' => 'Mie legendaris Bangka dengan kuah ikan tenggiri yang gurih ringan, taburan bawang goreng dan seledri.', 'harga' => 27000, 'gambar' => 'images/menu/Mie-Koba.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Kocok Bandung', 'deskripsi' => 'Mie gepeng dengan kuah kaldu sapi bening, kikil empuk, tauge, dan taburan bawang goreng renyah.', 'harga' => 27000, 'gambar' => 'images/menu/Mie-Kocok-Bandung.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Kopyok', 'deskripsi' => 'Mie khas Semarang dengan kuah bawang putih yang ringan, tahu pong, kerupuk gendar, dan lontong.', 'harga' => 25000, 'gambar' => 'images/menu/Mie-Kopyok.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Ongklok', 'deskripsi' => 'Mie rebus khas Wonosobo dalam kuah kental ebi yang gurih, disajikan dengan sate sapi dan tempe kemul.', 'harga' => 26000, 'gambar' => 'images/menu/mie-ongklok.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Rebus Godog Jawa', 'deskripsi' => 'Mie godog khas Jogja dimasak dengan api arang, kuah manis gurih, telur bebek, ayam kampung, dan kol.', 'harga' => 25000, 'gambar' => 'images/menu/Mie-rebus-godog-jawa.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Mie Tiaw', 'deskripsi' => 'Kwetiau lebar khas Pontianak yang kenyal, ditumis dengan daging sapi, tauge, dan kucai dalam bumbu bawang.', 'harga' => 28000, 'gambar' => 'images/menu/Mie-Tiaw.jpg'],
            ['id_kategori' => $mie, 'nama_menu' => 'Soto Mie Bogor', 'deskripsi' => 'Soto kuning hangat berisi mie, risoles, kol, tomat, dan daging sapi suwir dengan sambal dan jeruk nipis.', 'harga' => 27000, 'gambar' => 'images/menu/Soto-Mie-Bogor.jpg'],

            ['id_kategori' => $dingin, 'nama_menu' => 'Es Dawet', 'deskripsi' => 'Dawet kenyal pandan dengan santan gurih dan gula aren manis, disajikan dingin menyegarkan.', 'harga' => 12000, 'gambar' => 'images/menu/es-dawet.jpg'],
            ['id_kategori' => $dingin, 'nama_menu' => 'Es Goyobod', 'deskripsi' => 'Campuran goyobod kenyal, alpukat, kelapa muda, dan pacar cina dalam kuah santan manis dingin.', 'harga' => 13000, 'gambar' => 'images/menu/es-goyobod.jpg'],
            ['id_kategori' => $dingin, 'nama_menu' => 'Es Lidah Buaya', 'deskripsi' => 'Potongan lidah buaya segar yang kenyal dengan sirup lemon manis asam, dingin dan menenangkan tenggorokan.', 'harga' => 11000, 'gambar' => 'images/menu/es-lidah-buaya.jpg'],
            ['id_kategori' => $dingin, 'nama_menu' => 'Es Markisa', 'deskripsi' => 'Sirup markisa asli yang asam manis dengan biji buahnya, dicampur es serut yang segar.', 'harga' => 12000, 'gambar' => 'images/menu/es-markisa.jpg'],
            ['id_kategori' => $dingin, 'nama_menu' => 'Es Pisang Hijau', 'deskripsi' => 'Pisang raja dibalut adonan hijau pandan, disiram bubur sumsum, sirup cocopandan, dan es serut.', 'harga' => 14000, 'gambar' => 'images/menu/Es-Pisang-Hiijau.jpg'],

            ['id_kategori' => $panas, 'nama_menu' => 'Bajigur', 'deskripsi' => 'Minuman hangat santan dan gula aren dengan jahe dan kopi bubuk, disajikan dengan pisang rebus.', 'harga' => 10000, 'gambar' => 'images/menu/bajigur.jpg'],
            ['id_kategori' => $panas, 'nama_menu' => 'Kunyit Asem', 'deskripsi' => 'Jamu tradisional kunyit dan asam jawa yang segar, baik untuk daya tahan tubuh, disajikan hangat atau dingin.', 'harga' => 10000, 'gambar' => 'images/menu/kunyit-asem.jpg'],
            ['id_kategori' => $panas, 'nama_menu' => 'Sarabba', 'deskripsi' => 'Minuman hangat khas Makassar dari jahe, santan, gula aren, dan kuning telur, menghangatkan badan.', 'harga' => 11000, 'gambar' => 'images/menu/sarabba.jpg'],
            ['id_kategori' => $panas, 'nama_menu' => 'Sekoteng', 'deskripsi' => 'Minuman jahe hangat berisi kacang hijau, kacang tanah, pacar cina, dan potongan roti tawar.', 'harga' => 10000, 'gambar' => 'images/menu/sekoteng.jpg'],
            ['id_kategori' => $panas, 'nama_menu' => 'Wedang Jahe', 'deskripsi' => 'Wedang jahe bakar asli yang pedas hangat, dicampur gula batu dan serai untuk penghangat malam.', 'harga' => 9000, 'gambar' => 'images/menu/wedang-jahe.jpg'],

            ['id_kategori' => $cemilan, 'nama_menu' => 'Batagor Bandung', 'deskripsi' => 'Batagor ikan tenggiri goreng garing dengan tahu, disiram bumbu kacang gurih, kecap, dan jeruk limau.', 'harga' => 18000, 'gambar' => 'images/menu/Batagor-Bandung.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Burayot Garut', 'deskripsi' => 'Kue tradisional Garut dari tepung beras dan gula aren, digoreng hingga mengembang dengan ujung yang runcing.', 'harga' => 12000, 'gambar' => 'images/menu/Burayot-Garut.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Klepon Jawa', 'deskripsi' => 'Bola ketan pandan berisi gula merah lumer, dibalut parutan kelapa gurih, manis legit di setiap gigitan.', 'harga' => 10000, 'gambar' => 'images/menu/Klepon-Jawa.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Lumpia Tape Cokelat Jember', 'deskripsi' => 'Lumpia goreng renyah berisi tape singkong Jember dan cokelat lumer yang manis, cocok jadi teman ngopi.', 'harga' => 13000, 'gambar' => 'images/menu/Lumpia-Tape-Cokelat-Jember.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Otak-Otak Bakar Jakarta', 'deskripsi' => 'Otak-otak ikan tenggiri dibakar dalam daun pisang dengan bumbu santan gurih dan sambal kacang.', 'harga' => 20000, 'gambar' => 'images/menu/Otak-Otak-Bakar-Jakarta.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Pempek Palembang', 'deskripsi' => 'Pempek ikan tenggiri kenyal dengan kuah cuko pedas manis, dilengkapi mie kuning dan timun segar.', 'harga' => 22000, 'gambar' => 'images/menu/Pempek-Palembang.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Pisang Epe Makassar', 'deskripsi' => 'Pisang kepok bakar yang dipipihkan lalu disiram saus gula merah kental, manis legit dan smokey.', 'harga' => 15000, 'gambar' => 'images/menu/Pisang-Epe-Makassar.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Serabi Bandung', 'deskripsi' => 'Serabi santan yang lembut bersarang dengan topping kinca gula merah, manis dan gurih.', 'harga' => 12000, 'gambar' => 'images/menu/Serabi-Bandung.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Tahu Walik Banyuwangi', 'deskripsi' => 'Tahu goreng dibalik yang garing di luar lembut di dalam, berisi aci gurih dengan cocolan sambal petis.', 'harga' => 14000, 'gambar' => 'images/menu/Tahu-Walik-Banyuwangi.jpg'],
            ['id_kategori' => $cemilan, 'nama_menu' => 'Tempe Mendoan Purwokerto', 'deskripsi' => 'Tempe tipis berbalut tepung berbumbu ketumbar, digoreng setengah matang agar lembut, dicocol sambal kecap.', 'harga' => 12000, 'gambar' => 'images/menu/Tempe-Mendoan-Purwokerto.jpg'],
        ];

        foreach ($menus as $menu) {
            Menu::firstOrCreate(['nama_menu' => $menu['nama_menu']], $menu);
        }
    }
}
