<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('product_name');                 // salinan nama saat dibeli
            $table->string('unit', 20)->default('kg');      // salinan satuan saat dibeli
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('price');            // salinan harga saat dibeli
            $table->unsignedBigInteger('subtotal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
