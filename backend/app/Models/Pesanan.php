<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';
    protected $fillable = ['id_meja', 'id_waiter', 'tanggal_pesanan', 'status_pesanan', 'total_harga'];

    public function meja()
    {
        return $this->belongsTo(Meja::class, 'id_meja');
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'id_waiter');
    }

    public function detailPesanan()
    {
        return $this->hasMany(DetailPesanan::class, 'id_pesanan');
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'id_pesanan');
    }
}