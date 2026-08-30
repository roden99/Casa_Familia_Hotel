<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_to_supplier_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_to_supplier_id')->constrained('return_to_suppliers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->foreign('lot_id')->references('id')->on('product_lots')->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_to_supplier_items');
    }
};
