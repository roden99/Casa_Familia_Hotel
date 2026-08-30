<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->dropForeign('cpi_invoice_fk');
            $table->unsignedBigInteger('customer_account_invoice_id')->nullable()->change();
            $table->foreign('customer_account_invoice_id', 'cpi_invoice_fk')
                ->references('id')->on('customer_account_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->dropForeign('cpi_invoice_fk');
            $table->unsignedBigInteger('customer_account_invoice_id')->nullable(false)->change();
            $table->foreign('customer_account_invoice_id', 'cpi_invoice_fk')
                ->references('id')->on('customer_account_invoices')->onDelete('cascade');
        });
    }
};
