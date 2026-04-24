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
        Schema::table('refund_request_items', function (Blueprint $table) {
            $table->integer('approved_quantity')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_request_items', function (Blueprint $table) {
            //
        });
    }
};
