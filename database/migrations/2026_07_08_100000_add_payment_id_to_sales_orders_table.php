<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable()->after('terms');
            $table->foreign('payment_id', 'so_payment_fk')
                ->references('id')->on('customer_sales_account_payments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign('so_payment_fk');
            $table->dropColumn('payment_id');
        });
    }
};
