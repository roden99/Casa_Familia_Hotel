<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropForeign('cp_invoice_fk');
            $table->dropForeign('cp_csa_fk');
            $table->dropColumn(['customer_account_invoice_id', 'customer_sales_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_account_invoice_id')->nullable()->after('id');
            $table->foreign('customer_account_invoice_id', 'cp_invoice_fk')
                ->references('id')->on('customer_account_invoices')->onDelete('cascade');
            $table->unsignedBigInteger('customer_sales_account_id')->nullable()->after('customer_account_invoice_id');
            $table->foreign('customer_sales_account_id', 'cp_csa_fk')
                ->references('id')->on('customer_sales_account')->onDelete('cascade');
        });
    }
};
