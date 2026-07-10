<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullify blank/zero string values, then cast the rest to unsigned int
        DB::statement("UPDATE sales_orders SET terms = NULL WHERE terms IS NULL OR TRIM(terms) = '' OR TRIM(terms) = '0'");
        DB::statement("UPDATE sales_orders SET terms = CAST(terms AS UNSIGNED) WHERE terms IS NOT NULL");

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unsignedInteger('terms')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('terms')->nullable()->change();
        });
    }
};
