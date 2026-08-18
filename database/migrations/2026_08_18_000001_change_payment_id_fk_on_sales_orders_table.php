<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing FK only if both the column and the constraint exist
        if (Schema::hasColumn('sales_orders', 'payment_id')) {
            $fkExists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'sales_orders'
                  AND CONSTRAINT_NAME = 'so_payment_fk'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->isNotEmpty();

            Schema::table('sales_orders', function (Blueprint $table) use ($fkExists) {
                if ($fkExists) {
                    $table->dropForeign('so_payment_fk');
                }
                $table->foreign('payment_id', 'so_payment_fk')
                    ->references('id')->on('customer_payment_items')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign('so_payment_fk');
            $table->foreign('payment_id', 'so_payment_fk')
                ->references('id')->on('customer_sales_account_payments')->onDelete('set null');
        });
    }
};
