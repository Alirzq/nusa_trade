<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('farm_name')->nullable()->after('phone');        // nama kelompok tani / usaha tani
            $table->string('region', 100)->nullable()->after('farm_name');  // daerah, mis. "Cianjur, Jawa Barat"
            $table->string('role', 20)->default('seller')->after('password'); // admin | seller
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'farm_name', 'region', 'role']);
        });
    }
};
