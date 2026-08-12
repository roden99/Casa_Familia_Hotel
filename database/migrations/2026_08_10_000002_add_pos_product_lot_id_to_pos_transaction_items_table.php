<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            $table->foreignId('pos_product_lot_id')
                ->nullable()
                ->after('product_id')
                ->constrained('pos_product_lots')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            $table->dropForeign(['pos_product_lot_id']);
            $table->dropColumn('pos_product_lot_id');
        });
    }
};
