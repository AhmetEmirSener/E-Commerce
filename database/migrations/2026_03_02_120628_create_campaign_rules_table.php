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
        Schema::create('campaign_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('field');
            $table->enum('operator', ['=', '>', '<', '>=', '<=', 'in', 'like']);
            $table->string('value');
            $table->timestamps();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->index(['campaign_id']);
            $table->index(['field']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_rules');
    }
};
