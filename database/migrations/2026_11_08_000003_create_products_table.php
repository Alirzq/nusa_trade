<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');          // harga per satuan, dalam Rupiah
            $table->unsignedInteger('stock')->default(0); // stok dalam satuan jual
            $table->string('unit', 20)->default('kg');    // satuan jual: kg, ton, ikat, karung, ...
            $table->unsignedInteger('min_order')->default(1); // minimal pembelian (dalam satuan)
            $table->date('harvest_date')->nullable();     // tanggal panen
            $table->boolean('is_organic')->default(false);
            $table->string('origin_region', 100)->nullable(); // asal daerah, mis. "Cianjur, Jawa Barat"
            $table->string('image_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
