<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pembeli tidak punya akun, jadi data pembeli disimpan langsung di pesanan.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('access_token', 64);              // hash SHA-256 dari token pembeli
            $table->string('buyer_name');
            $table->string('buyer_phone', 20);
            $table->string('status', 20)->default('pending'); // pending|paid|shipped|completed|cancelled
            $table->unsignedBigInteger('total');
            $table->text('shipping_address');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
