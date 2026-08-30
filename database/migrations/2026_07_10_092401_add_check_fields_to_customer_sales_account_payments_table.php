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
        Schema::table('customer_sales_account_payments', function (Blueprint $table) {
            $table->date('check_date')->nullable()->after('payment_method');
            $table->string('check_number')->nullable()->after('check_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_sales_account_payments', function (Blueprint $table) {
            $table->dropColumn(['check_date', 'check_number']);
        });
    }
};
