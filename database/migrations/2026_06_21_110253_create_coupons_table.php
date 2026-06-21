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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index();
            $table->enum('discount_type', ['percent', 'fixed']);
            $table->float('discount_value');
            $table->unsignedInteger('usage_limit');
            $table->unsignedTinyInteger('user_usage_limit');
            $table->enum('coupon_type', ['standard', 'flash']);
            $table->unsignedTinyInteger('reserve_minutes');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_active');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
