<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fruits', function (Blueprint $table) {
            $table->decimal('base_price', 10, 2)->default(0)->after('name');
        });

        // Populate base_price with sensible defaults for seeded fruits
        DB::table('fruits')->where('name', 'Maçã')->update(['base_price' => 10.50]);
        DB::table('fruits')->where('name', 'Banana')->update(['base_price' => 8.00]);
        DB::table('fruits')->where('name', 'Pera')->update(['base_price' => 7.25]);
        DB::table('fruits')->where('name', 'Abacaxi')->update(['base_price' => 20.50]);

        // Insert new fruits with base_price
        $newFruits = [
            ['name' => 'Banana nanica', 'base_price' => 7.50, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Banana prata', 'base_price' => 8.50, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Banana escovada', 'base_price' => 12.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tomate', 'base_price' => 15.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pimentinha', 'base_price' => 18.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pimentao', 'base_price' => 25.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cenoura', 'base_price' => 14.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Melancia', 'base_price' => 22.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Abacate', 'base_price' => 22.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Couve', 'base_price' => 22.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chicoria', 'base_price' => 22.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Uva kids', 'base_price' => 22.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Xuxu', 'base_price' => 22.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maca miuda', 'base_price' => 28.00, 'price' => 0, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('fruits')->insert($newFruits);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fruits', function (Blueprint $table) {
            $table->dropColumn('base_price');
        });
    }
};
