<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meja extends Model
{
    protected $table = 'meja';
    protected $fillable = ['nomor_meja', 'qr_code', 'status_meja'];

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'id_meja');
    }
}