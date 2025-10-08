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
        Schema::create('adverts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->integer('total_comments')->nullable();
            $table->json('images')->nullable();
            $table->string('slug')->nullable();
            $table->integer('views')->default(0);
            $table->decimal('price',10,2);
            $table->timestamp('expires_at')->nullable();
            $table->enum('status',['Aktif','Pasif','Beklemede']);
            $table->boolean('is_featured')->default(false);

            
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adverts');
    }
};
