<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'id_pesanan' => [
                'required',
                'exists:pesanan,id',
                Rule::unique('pembayaran', 'id_pesanan'),
            ],
            'metode_pembayaran' => 'required|in:qris,tunai',
            'total_bayar' => 'required|numeric|min:0|decimal:0,2',
            'nomor_struk_digital' => 'required|string|unique:pembayaran,nomor_struk_digital',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $pesanan = Pesanan::whereKey($data['id_pesanan'])
                ->lockForUpdate()
                ->first();

            if (!$pesanan) {
                throw ValidationException::withMessages([
                    'id_pesanan' => ['Pesanan tidak ditemukan.'],
                ]);
            }

            if (Pembayaran::where('id_pesanan', $pesanan->id)->exists()) {
                throw ValidationException::withMessages([
                    'id_pesanan' => ['Pesanan sudah dibayar.'],
                ]);
            }

            if (number_format((float) $data['total_bayar'], 2, '.', '') !== number_format((float) $pesanan->total_harga, 2, '.', '')) {
                throw ValidationException::withMessages([
                    'total_bayar' => ['Total pembayaran harus sama dengan total pesanan.'],
                ]);
            }

            $pembayaran = Pembayaran::create([
                ...$data,
                'id_kasir' => $request->user()->id,
                'tanggal_bayar' => now(),
            ]);

            $pesanan->load('meja');
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