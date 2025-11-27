<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('fruit_id')->nullable()->constrained('fruits')->nullOnDelete();
            $table->string('product_name');
            $table->enum('unit', ['box', 'kg'])->default('box');
            $table->decimal('price', 12, 2)->default(0); // price per box (snapshot)
            $table->decimal('kg_per_box', 10, 2)->nullable();
            $table->decimal('qty', 12, 3)->default(0); // can be fractional for kg
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
