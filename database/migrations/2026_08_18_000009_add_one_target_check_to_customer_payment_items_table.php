<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE customer_payment_items
            ADD CONSTRAINT cpi_one_target CHECK (
                (customer_sales_account_id IS NOT NULL) +
                (customer_account_invoice_id IS NOT NULL) +
                (sales_order_id IS NOT NULL) = 1
            )
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE customer_payment_items DROP CONSTRAINT cpi_one_target');
    }
};
