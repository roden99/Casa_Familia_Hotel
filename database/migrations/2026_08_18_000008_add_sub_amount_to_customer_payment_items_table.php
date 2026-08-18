<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->decimal('sub_amount', 12, 2)->default(0)->after('sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payment_items', function (Blueprint $table) {
            $table->dropColumn('sub_amount');
        });
    }
};
