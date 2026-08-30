<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_sales_account_id')->nullable()->after('id');
            $table->foreign('customer_sales_account_id', 'cpi_csa_fk')
                ->references('id')->on('customer_sales_account')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->dropForeign('cpi_csa_fk');
            $table->dropColumn('customer_sales_account_id');
        });
    }
};
