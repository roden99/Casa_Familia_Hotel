<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('pos_transaction_items', 'product_id')) {
                $table->dropColumn('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('id');
        });
    }
};
