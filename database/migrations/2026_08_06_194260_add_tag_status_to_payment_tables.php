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
        if (!Schema::hasColumn('customer_sales_account_payments', 'tag_status')) {
            Schema::table('customer_sales_account_payments', function (Blueprint $table) {
                $table->string('tag_status')->default('Untagged');
            });
        }

        if (!Schema::hasColumn('customer_account_invoice_payments', 'tag_status')) {
            Schema::table('customer_account_invoice_payments', function (Blueprint $table) {
                $table->string('tag_status')->default('Untagged');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_sales_account_payments', function (Blueprint $table) {
            $table->dropColumn('tag_status');
        });

        Schema::table('customer_account_invoice_payments', function (Blueprint $table) {
            $table->dropColumn('tag_status');
        });
    }
};
