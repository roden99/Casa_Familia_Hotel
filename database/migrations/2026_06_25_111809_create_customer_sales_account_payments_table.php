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
        Schema::create('customer_sales_account_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_sales_account_id');
            $table->foreign('customer_sales_account_id', 'csa_payments_csa_fk')
                ->references('id')->on('customer_sales_account')->onDelete('cascade');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('reference_no')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_sales_account_payments');
    }
};
