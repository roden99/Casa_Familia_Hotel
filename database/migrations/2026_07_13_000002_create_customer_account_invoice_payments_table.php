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
        Schema::create('customer_account_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_account_invoice_id');
            $table->foreign('customer_account_invoice_id', 'caip_cai_fk')
                ->references('id')->on('customer_account_invoices')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('reference_no')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('check_date')->nullable();
            $table->string('check_number')->nullable();
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
        Schema::dropIfExists('customer_account_invoice_payments');
    }
};
