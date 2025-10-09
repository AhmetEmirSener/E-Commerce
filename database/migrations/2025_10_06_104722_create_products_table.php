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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('category_id')->index();
            $table->unsignedBigInteger('brand_id')->index()->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->integer('discount_stock')->nullable();
            $table->boolean('is_discount_active')->default(false);
            $table->json('image')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('slug')->nullable();
            $table->enum('status',['aktif','pasif','beklemede'])->default('aktif');
            
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
