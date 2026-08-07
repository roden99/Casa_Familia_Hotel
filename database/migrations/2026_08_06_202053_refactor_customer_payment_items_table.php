<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename FK column to match renamed parent table
        Schema::table('customer_account_invoice_payment_items', function (Blueprint $table) {
            $table->renameColumn('customer_account_invoice_payment_id', 'customer_payment_id');
        });

        // Add XOR check constraint: exactly one of invoice_id or sales_order_id must be set
        DB::statement('
            ALTER TABLE customer_account_invoice_payment_items
            ADD CONSTRAINT chk_payment_item_target
            CHECK (
                (customer_account_invoice_id IS NOT NULL AND sales_order_id IS NULL)
                OR
                (customer_account_invoice_id IS NULL AND sales_order_id IS NOT NULL)
            )
        ');

        // Rename table to cleaner name
        Schema::rename('customer_account_invoice_payment_items', 'customer_payment_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customer_payment_items')) {
            Schema::rename('customer_payment_items', 'customer_account_invoice_payment_items');
        }

        try {
            DB::statement('
                ALTER TABLE customer_account_invoice_payment_items
                DROP CHECK chk_payment_item_target
            ');
        } catch (\Exception $e) {
            // Constraint may not exist
        }

        if (Schema::hasColumn('customer_account_invoice_payment_items', 'customer_payment_id')) {
            Schema::table('customer_account_invoice_payment_items', function (Blueprint $table) {
                $table->renameColumn('customer_payment_id', 'customer_account_invoice_payment_id');
            });
        }
    }
};
