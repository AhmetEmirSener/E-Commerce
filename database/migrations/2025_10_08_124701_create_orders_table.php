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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamp('ordered_at')->nullable();
            $table->unsignedBigInteger('users_address_id')->index();
            $table->string('cargo_company')->nullable();
            $table->string('cargo_code')->nullable();
            $table->timestamp('cargo_has_at')->nullable();
            $table->timestamp('delivery_at')->nullable();
            $table->decimal('total',10,2);
            $table->string('invoice')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('status')->default('Sipariş alındı.');


            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('users_address_id')->references('id')->on('user_addresses')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
