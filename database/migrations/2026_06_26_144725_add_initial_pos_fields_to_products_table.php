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
        Schema::table('products', function (Blueprint $table) {
            $table->date('initial_pos_date')->nullable()->after('pos_qty');
            $table->decimal('initial_pos_qty', 10, 4)->nullable()->after('initial_pos_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['initial_pos_date', 'initial_pos_qty']);
        });
    }
};
