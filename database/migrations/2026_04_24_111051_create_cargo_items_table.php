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
        Schema::create('cargo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_cargo_detail_id')->constrained('order_cargo_details')->cascadeOnDelete();;
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();;
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargo_items');
    }
};
