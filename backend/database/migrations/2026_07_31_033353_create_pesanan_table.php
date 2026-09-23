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
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_meja')->constrained('meja')->cascadeOnDelete();
            $table->foreignId('id_waiter')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_pesanan')->useCurrent();
            $table->enum('status_pesanan', ['baru', 'diproses', 'selesai'])->default('baru');
            $table->decimal('total_harga', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
