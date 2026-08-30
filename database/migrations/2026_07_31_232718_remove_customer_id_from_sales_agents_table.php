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
        Schema::table('sales_agents', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropUnique(['customer_id']);
        });

        Schema::table('sales_agents', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_agents', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unique('customer_id');
        });
    }
};
