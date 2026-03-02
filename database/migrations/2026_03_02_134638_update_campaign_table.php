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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent')->after('link');
            $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            $table->integer('priority')->default(0)->after('discount_value');
            $table->boolean('exclusive')->default(true)->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'priority', 'exclusive']);
        });
    }
};
