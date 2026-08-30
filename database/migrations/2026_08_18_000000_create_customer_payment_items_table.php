<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_account_invoice_id');
            $table->foreign('customer_account_invoice_id', 'cpi_invoice_fk')
                ->references('id')->on('customer_account_invoices')->onDelete('cascade');
            $table->unsignedBigInteger('customer_sales_account_payment_id');
            $table->foreign('customer_sales_account_payment_id', 'cpi_payment_fk')
                ->references('id')->on('customer_sales_account_payments')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_items');
    }
};
