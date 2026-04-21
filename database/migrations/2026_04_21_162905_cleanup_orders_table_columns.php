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
            $table->dropColumn([
                'cargo_company', 
                'cargo_code', 
                'cargo_has_at', 
                'delivery_at', 
                'installment_fee',
                'installment',
                'payment_status',
                'payment_id',
                'save_card'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cargo_company')->nullable();
            $table->string('cargo_code')->nullable();
            $table->timestamp('cargo_has_at')->nullable();
            $table->timestamp('delivery_at')->nullable();
            $table->decimal('installment_fee', 10, 2)->nullable();
            $table->integer('installment')->nullable();
            $table->string('payment_status')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->boolean('save_card')->default(false);
        });
    }
};
