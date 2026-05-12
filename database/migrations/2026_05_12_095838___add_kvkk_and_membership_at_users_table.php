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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('kvkk_accepted_at')->nullable()->after('role');
            $table->timestamp('membership_accepted_at')->nullable()->after('kvkk_accepted_at');
            $table->timestamp('marketing_consent_at')->nullable()->after('membership_accepted_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'kvkk_accepted_at',
                'membership_accepted_at',
                'marketing_consent_at',
            ]);
        });
    }
};
