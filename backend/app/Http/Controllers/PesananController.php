<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\DetailPesanan;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PesananController extends Controller
{
    public function index()
    {
        return Pesanan::with(['meja', 'waiter', 'detailPesanan.menu'])
            ->latest()
            ->get();
    }

    // Publik — dipakai pelanggan untuk cek status pesanan di mejanya sendiri.
    // Pesanan tetap tampil selama BELUM ada record di tabel pembayaran untuk
    // id pesanan tsb (tabel pembayaran tidak punya kolom status — begitu ada
    // barisnya di situ, artinya sudah dibayar/lunas), meskipun status dapurnya
    // sudah "selesai" (supaya pelanggan tahu harus ambil & bayar dulu).
    public function statusByMeja($idMeja)
    {
        return Pesanan::with('detailPesanan.menu')
            ->where('id_meja', $idMeja)
            ->whereNotIn('id', function ($query) {
                $query->select('id_pesanan')
                    ->from('pembayaran');
            })
            ->latest()
            ->get();
    }

    // Dipakai oleh halaman Kasir — daftar pesanan yang statusnya sudah
    // "selesai" dari dapur, tapi belum ada record pembayarannya (belum lunas).
    public function siapBayar()
    {
        return Pesanan::with(['meja', 'detailPesanan.menu'])
            ->where('status_pesanan', 'selesai')
            ->whereNotIn('id', function ($query) {
                $query->select('id_pesanan')
                    ->from('pembayaran');
            })
            ->latest()
            ->get();
    }

    // Dipakai baik oleh pelanggan (scan QR) maupun waiter (input manual)
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_meja' => 'required|exists:meja,id',
            'nama_pelanggan' => 'required|string|max:60',
            'id_waiter' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.id_menu' => 'required|exists:menu,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $total = 0;
            $menus = Menu::whereIn('id', collect($data['items'])->pluck('id_menu'))->get()->keyBy('id');

            $pesanan = Pesanan::create([
                'id_meja' => $data['id_meja'],
                'nama_pelanggan' => $data['nama_pelanggan'],
                'id_waiter' => $data['id_waiter'] ?? null,
                'tanggal_pesanan' => now(),
                'status_pesanan' => 'baru',
                'total_harga' => 0,
            ]);

            foreach ($data['items'] as $item) {
                $menu = $menus[$item['id_menu']];
                $subtotal = $menu->harga * $item['jumlah'];
                $total += $subtotal;

                DetailPesanan::create([
                    'id_pesanan' => $pesanan->id,
                    'id_menu' => $menu->id,
                    'jumlah' => $item['jumlah'],
                    'catatan' => $item['catatan'] ?? null,
                    'subtotal' => $subtotal,
                ]);
            }

            $pesanan->update(['total_harga' => $total]);

            // Tandai meja jadi "terisi" begitu ada pesanan baru masuk
            $pesanan->meja()->update(['status_meja' => 'terisi']);

            return $pesanan->load('detailPesanan.menu', 'meja');
        });
    }

    // Field yang divalidasi di sini adalah "status_pesanan" -- pastikan
    // frontend (api.js -> updateOrderStatus) mengirim key JSON yang sama persis.
    public function updateStatus(Request $request, Pesanan $pesanan)
    {
        $data = $request->validate([
            'status_pesanan' => 'required|in:baru,diproses,selesai',
        ]);

        $pesanan->update($data);
        return $pesanan;
    }
}