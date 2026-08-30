<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_delivery_id')->constrained('pos_deliveries')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedBigInteger('pos_product_lot_id')->nullable();
            $table->foreign('pos_product_lot_id')->references('id')->on('pos_product_lots')->nullOnDelete();
            $table->decimal('quantity', 10, 4);
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_delivery_items');
    }
};
