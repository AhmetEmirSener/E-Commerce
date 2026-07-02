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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('coupon_discount', 10, 2)->default(0.00)->after('discount_total');
            $table->unsignedBigInteger('coupon_id')->nullable()->after('coupon_discount');

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']); 
            
            $table->dropColumn(['coupon_id', 'coupon_discount']);
        });
    }
};
