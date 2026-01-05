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
        Schema::create('slider_items', function (Blueprint $table) {
            $table->id();
            $table->unSignedBigInteger('slider_id');
            $table->string('ref_type');
            $table->integer('ref_id')->nullable();
            $table->string('image')->nullable();
            $table->string('mobile_image')->nullable();
            $table->string('link')->nullable();
            $table->integer('sort')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('slider_id')->references('id')->on('sliders')->onDelete('cascade');
            $table->index(['slider_id', 'is_active']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slider_items');
    }
};
