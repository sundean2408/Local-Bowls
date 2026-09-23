<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MejaController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Publik — dipakai halaman pelanggan (scan QR) tanpa login
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/kategori', [KategoriController::class, 'index']);
Route::get('/meja', [MejaController::class, 'index']);
Route::post('/pesanan', [PesananController::class, 'store']);
Route::get('/meja/{idMeja}/pesanan', [PesananController::class, 'statusByMeja']);

Route::post('/login', [AuthController::class, 'login']);

// Perlu login (Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('kategori', KategoriController::class)->except('index');
    Route::apiResource('menu', MenuController::class)->except('index');
    Route::apiResource('meja', MejaController::class)->except('index');
    Route::get('/pesanan', [PesananController::class, 'index']);
    Route::get('/pesanan/siap-bayar', [PesananController::class, 'siapBayar']);
    Route::patch('/pesanan/{pesanan}/status', [PesananController::class, 'updateStatus']);
    Route::apiResource('pembayaran', PembayaranController::class);
    Route::get('/laporan', [LaporanController::class, 'index']);
});