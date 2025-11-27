<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fruits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        // Seed initial fruits
        DB::table('fruits')->insert([
            ['name' => 'Maçã', 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Banana', 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pera', 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Abacaxi', 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fruits');
    }
};
