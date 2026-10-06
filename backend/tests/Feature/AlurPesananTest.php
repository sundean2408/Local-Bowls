<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AlurPesananTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'nama' => 'Admin',
            'username' => 'admin-test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);
    }

    public function test_pelanggan_bisa_lihat_menu_dan_buat_pesanan(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Mie']);
        $menu = Menu::create([
            'id_kategori' => $kategori->id,
            'nama_menu' => 'Mie Aceh',
            'harga' => 30000,
            'status_tersedia' => true,
        ]);
        $meja = Meja::create(['nomor_meja' => '1', 'status_meja' => 'kosong']);

        $this->getJson('/api/menu')->assertOk();
        $this->getJson('/api/meja')->assertOk();

        $res = $this->postJson('/api/pesanan', [
            'id_meja' => $meja->id,
            'nama_pelanggan' => 'Dinda',
            'items' => [['id_menu' => $menu->id, 'jumlah' => 2]],
        ]);

        $res->assertCreated();
        $this->assertEquals(60000, (float) $res->json('total_harga'));

        $this->assertDatabaseHas('pesanan', ['id_meja' => $meja->id, 'nama_pelanggan' => 'Dinda']);
        $this->assertDatabaseHas('meja', ['id' => $meja->id, 'status_meja' => 'terisi']);
    }

    public function test_menu_habis_tidak_bisa_dipesan(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Mie']);
        $menu = Menu::create([
            'id_kategori' => $kategori->id,
            'nama_menu' => 'Mie Habis',
            'harga' => 25000,
            'status_tersedia' => false,
        ]);
        $meja = Meja::create(['nomor_meja' => '2', 'status_meja' => 'kosong']);

        $this->postJson('/api/pesanan', [
            'id_meja' => $meja->id,
            'nama_pelanggan' => 'Tamu',
            'items' => [['id_menu' => $menu->id, 'jumlah' => 1]],
        ])->assertStatus(422);
    }

    public function test_dapur_maju_status_dan_kasir_bayar_sampai_meja_kosong(): void
    {
        $admin = $this->admin();
        $kategori = Kategori::create(['nama_kategori' => 'Mie']);
        $menu = Menu::create([
            'id_kategori' => $kategori->id,
            'nama_menu' => 'Mie Koba',
            'harga' => 27000,
            'status_tersedia' => true,
        ]);
        $meja = Meja::create(['nomor_meja' => '3', 'status_meja' => 'kosong']);

        $pesananId = $this->postJson('/api/pesanan', [
            'id_meja' => $meja->id,
            'nama_pelanggan' => 'Budi',
            'items' => [['id_menu' => $menu->id, 'jumlah' => 1]],
        ])->assertCreated()->json('id');

        // Dapur tanpa login ditolak
        $this->patchJson("/api/pesanan/{$pesananId}/status", ['status_pesanan' => 'diproses'])
            ->assertUnauthorized();

        // Dapur maju: baru -> diproses -> selesai
        $kitchen = User::create([
            'nama' => 'Dapur', 'username' => 'kitchen-test',
            'password' => Hash::make('secret123'), 'role' => 'kitchen',
        ]);
        $this->actingAs($kitchen, 'sanctum')
            ->patchJson("/api/pesanan/{$pesananId}/status", ['status_pesanan' => 'diproses'])
            ->assertOk();
        $this->actingAs($kitchen, 'sanctum')
            ->patchJson("/api/pesanan/{$pesananId}/status", ['status_pesanan' => 'selesai'])
            ->assertOk();

        // Kasir lihat antrean siap bayar, lalu bayar tunai pas
        $kasir = User::create([
            'nama' => 'Kasir', 'username' => 'kasir-test',
            'password' => Hash::make('secret123'), 'role' => 'kasir',
        ]);
        $this->actingAs($kasir, 'sanctum')
            ->getJson('/api/pesanan/siap-bayar')
            ->assertOk()
            ->assertJsonFragment(['id' => $pesananId]);

        $this->actingAs($kasir, 'sanctum')->postJson('/api/pembayaran', [
            'id_pesanan' => $pesananId,
            'metode_pembayaran' => 'tunai',
            'total_bayar' => 27000,
            'nomor_struk_digital' => 'STRK-TEST-001',
        ])->assertCreated();

        // Bayar dua kali ditolak
        $this->actingAs($kasir, 'sanctum')->postJson('/api/pembayaran', [
            'id_pesanan' => $pesananId,
            'metode_pembayaran' => 'tunai',
            'total_bayar' => 27000,
            'nomor_struk_digital' => 'STRK-TEST-002',
        ])->assertStatus(422);

        $this->assertDatabaseHas('meja', ['id' => $meja->id, 'status_meja' => 'kosong']);

        // Laporan admin tampil
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/laporan')
            ->assertOk()
            ->assertJsonPath('jumlah_transaksi_hari_ini', 1);
    }

    public function test_role_ditolak_di_endpoint_lain(): void
    {
        $kasir = User::create([
            'nama' => 'Kasir', 'username' => 'kasir-role',
            'password' => Hash::make('secret123'), 'role' => 'kasir',
        ]);

        // Kasir tidak boleh buka laporan admin
        $this->actingAs($kasir, 'sanctum')->getJson('/api/laporan')->assertForbidden();
        // Kasir tidak boleh lihat daftar pesanan dapur (dapur/admin only)
        $this->actingAs($kasir, 'sanctum')->getJson('/api/pesanan')->assertForbidden();
        // Pesanan 999 tidak ada -> 404, bukan bukti role. Cek kasir ditolak di /pembayaran (kasir boleh).
        // Ganti cek: waiter tidak ada role -> kasir di /users juga ditolak.
        $this->actingAs($kasir, 'sanctum')->getJson('/api/users')->assertForbidden();
    }
}
