<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $fillable = ['id_pesanan', 'id_kasir', 'tanggal_bayar', 'metode_pembayaran', 'total_bayar', 'nomor_struk_digital'];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'id_kasir');
    }
}