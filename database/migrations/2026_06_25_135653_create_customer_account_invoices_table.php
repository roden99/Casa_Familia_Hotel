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
        Schema::create('customer_account_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_sales_account_id');
            $table->foreign('customer_sales_account_id', 'cai_csa_fk')
                  ->references('id')->on('customer_sales_account')->onDelete('cascade');
            $table->string('reference_no')->nullable();
            $table->date('invoice_date');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_account_invoices');
    }
};
