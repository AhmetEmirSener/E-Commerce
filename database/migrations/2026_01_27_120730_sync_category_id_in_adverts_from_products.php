<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('
            UPDATE adverts
            JOIN products ON products.id = adverts.product_id
            SET adverts.category_id = products.category_id
        ');
    }

    public function down(): void
    {
        // geri alma zor, boş bırakabilirsin
    }
};
