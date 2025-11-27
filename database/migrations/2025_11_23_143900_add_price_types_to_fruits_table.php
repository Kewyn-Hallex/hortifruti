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
        Schema::table('fruits', function (Blueprint $table) {
            $table->decimal('price_box', 10, 2)->default(0)->after('price');
            $table->decimal('price_kg', 10, 2)->default(0)->after('price_box');
            $table->decimal('price_bunch', 10, 2)->default(0)->after('price_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fruits', function (Blueprint $table) {
            $table->dropColumn(['price_box', 'price_kg', 'price_bunch']);
        });
    }
};
