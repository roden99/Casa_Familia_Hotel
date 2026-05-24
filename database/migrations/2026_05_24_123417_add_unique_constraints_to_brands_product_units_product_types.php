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
        Schema::table('brands', function (Blueprint $table) {
            $table->unique('brandname');
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->unique('unit_name');
            $table->unique('unit_code');
        });

        Schema::table('product_types', function (Blueprint $table) {
            $table->unique('type_name');
            $table->unique('type_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique(['brandname']);
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->dropUnique(['unit_name']);
            $table->dropUnique(['unit_code']);
        });

        Schema::table('product_types', function (Blueprint $table) {
            $table->dropUnique(['type_name']);
            $table->dropUnique(['type_code']);
        });
    }
};
