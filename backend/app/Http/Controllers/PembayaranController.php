<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    public function index()
    {
        return Pembayaran::with(['pesanan.meja', 'pesanan.detailPesanan.menu', 'kasir'])
            ->latest('tanggal_bayar')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_pesanan' => 'required|exists:pesanan,id',
            'metode_pembayaran' => 'required|in:qris,tunai',
            'total_bayar' => 'required|numeric|min:0',
            'nomor_struk_digital' => 'required|string|unique:pembayaran,nomor_struk_digital',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $pembayaran = Pembayaran::create([
                ...$data,
                'id_kasir' => $request->user()->id,
                'tanggal_bayar' => now(),
            ]);

            $pesanan = Pesanan::with('meja')->find($data['id_pesanan']);
            $pesanan->update(['status_pesanan' => 'selesai']);
            $pesanan->meja?->update(['status_meja' => 'kosong']);

            return $pembayaran->load('pesanan.meja', 'kasir');
        });
    }

    public function show(Pembayaran $pembayaran)
    {
        return $pembayaran->load(['pesanan.meja', 'pesanan.detailPesanan.menu', 'kasir']);
    }

    public function update(Request $request, Pembayaran $pembayaran)
    {
        $data = $request->validate([
            'metode_pembayaran' => 'sometimes|in:qris,tunai',
            'total_bayar' => 'sometimes|numeric|min:0',
        ]);

        $pembayaran->update($data);
        return $pembayaran;
    }

    public function destroy(Pembayaran $pembayaran)
    {
        $pembayaran->delete();
        return response()->json(['message' => 'Pembayaran dihapus']);
    }
}