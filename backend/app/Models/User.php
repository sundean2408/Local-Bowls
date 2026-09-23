<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['nama', 'username', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function pesananSebagaiWaiter()
    {
        return $this->hasMany(Pesanan::class, 'id_waiter');
    }

    public function pembayaranSebagaiKasir()
    {
        return $this->hasMany(Pembayaran::class, 'id_kasir');
    }
}