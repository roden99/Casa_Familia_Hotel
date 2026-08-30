<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_account_invoice_id')->nullable();
            $table->foreign('customer_account_invoice_id', 'cp_invoice_fk')
                ->references('id')->on('customer_account_invoices')->onDelete('cascade');
            $table->unsignedBigInteger('customer_sales_account_id')->nullable();
            $table->foreign('customer_sales_account_id', 'cp_csa_fk')
                ->references('id')->on('customer_sales_account')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('reference_no')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('check_date')->nullable();
            $table->string('check_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->string('tag_status')->nullable();
        });

        // Swap customer_payment_items FK from customer_sales_account_payments → customer_payments
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->dropForeign('cpi_payment_fk');
            $table->renameColumn('customer_sales_account_payment_id', 'customer_payment_id');
        });
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->foreign('customer_payment_id', 'cpi_payment_fk')
                ->references('id')->on('customer_payments')->onDelete('cascade');
        });

        Schema::dropIfExists('customer_account_invoice_payments');
        Schema::dropIfExists('customer_sales_account_payments');
    }

    public function down(): void
    {
        Schema::create('customer_sales_account_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_sales_account_id');
            $table->foreign('customer_sales_account_id', 'csa_payments_csa_fk')
                ->references('id')->on('customer_sales_account')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('reference_no')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('check_date')->nullable();
            $table->string('check_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->string('tag_status')->nullable();
        });

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
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->string('tag_status')->nullable();
        });

        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->dropForeign('cpi_payment_fk');
            $table->renameColumn('customer_payment_id', 'customer_sales_account_payment_id');
        });
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->foreign('customer_sales_account_payment_id', 'cpi_payment_fk')
                ->references('id')->on('customer_sales_account_payments')->onDelete('cascade');
        });

        Schema::dropIfExists('customer_payments');
    }
};
