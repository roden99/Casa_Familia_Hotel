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
        // Add FK from payment_items → customer_account_invoices
        Schema::table('customer_account_invoice_payment_items', function (Blueprint $table) {
            $table->foreign('customer_account_invoice_id', 'caip_items_invoice_fk')
                ->references('id')->on('customer_account_invoices')
                ->onDelete('cascade');
        });

        // Drop FK then column from customer_account_invoice_payments
        Schema::table('customer_account_invoice_payments', function (Blueprint $table) {
            $table->dropForeign('caip_cai_fk');
            $table->dropColumn('customer_account_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the FK if it still exists
        $fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_account_invoice_payment_items' AND CONSTRAINT_NAME = 'caip_items_invoice_fk' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        if (!empty($fks)) {
            Schema::table('customer_account_invoice_payment_items', function (Blueprint $table) {
                $table->dropForeign('caip_items_invoice_fk');
            });
        }

        // Add the column back if it doesn't exist yet
        if (!Schema::hasColumn('customer_account_invoice_payments', 'customer_account_invoice_id')) {
            Schema::table('customer_account_invoice_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_account_invoice_id')->nullable()->after('id');
            });
        }

        // Add the FK if it doesn't exist yet
        // Note: skipped — data may be invalid after partial migration runs
    }
};
