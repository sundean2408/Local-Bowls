<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pesanan')->constrained('pesanan')->cascadeOnDelete();
            $table->foreignId('id_kasir')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_bayar')->useCurrent();
            $table->enum('metode_pembayaran', ['qris', 'tunai'])->default('qris');
            $table->decimal('total_bayar', 10, 2);
            $table->string('nomor_struk_digital')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
