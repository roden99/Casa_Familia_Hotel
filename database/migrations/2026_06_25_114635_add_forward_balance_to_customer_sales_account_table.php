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
        Schema::table('customer_sales_account', function (Blueprint $table) {
            $table->decimal('forward_balance', 12, 2)->default(0)->after('discount_percentage');
            $table->date('forward_balance_date')->nullable()->after('forward_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_sales_account', function (Blueprint $table) {
            $table->dropColumn(['forward_balance', 'forward_balance_date']);
        });
    }
};
