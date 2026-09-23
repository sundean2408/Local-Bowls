<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\DetailPesanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index()
    {
        $hariIni = Carbon::today();
        $awalMinggu = Carbon::now()->startOfWeek();
        $awalBulan = Carbon::now()->startOfMonth();

        $totalHariIni = Pembayaran::whereDate('tanggal_bayar', $hariIni)->sum('total_bayar');
        $totalMingguIni = Pembayaran::where('tanggal_bayar', '>=', $awalMinggu)->sum('total_bayar');
        $totalBulanIni = Pembayaran::where('tanggal_bayar', '>=', $awalBulan)->sum('total_bayar');
        $jumlahTransaksiHariIni = Pembayaran::whereDate('tanggal_bayar', $hariIni)->count();

        // 5 menu paling laku, dihitung dari detail pesanan yang sudah dibayar
        $menuTerlaris = DetailPesanan::select('id_menu', DB::raw('SUM(jumlah) as total_terjual'))
            ->whereIn('id_pesanan', function ($query) {
                $query->select('id_pesanan')->from('pembayaran');
            })
            ->groupBy('id_menu')
            ->orderByDesc('total_terjual')
            ->with('menu')
            ->take(5)
            ->get();

        // 10 transaksi terbaru
        $transaksiTerbaru = Pembayaran::with(['pesanan.meja', 'kasir'])
            ->latest('tanggal_bayar')
            ->take(10)
            ->get();

        return response()->json([
            'total_hari_ini' => (float) $totalHariIni,
            'total_minggu_ini' => (float) $totalMingguIni,
            'total_bulan_ini' => (float) $totalBulanIni,
            'jumlah_transaksi_hari_ini' => $jumlahTransaksiHariIni,
            'menu_terlaris' => $menuTerlaris,
            'transaksi_terbaru' => $transaksiTerbaru,
        ]);
    }
}