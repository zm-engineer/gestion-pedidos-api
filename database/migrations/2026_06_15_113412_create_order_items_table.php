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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');     // si se borra una orden, sus líneas también
            $table->foreignId('product_id')->constrained()->onDelete('restrict');  // preserva el histórico: no se puede borrar un producto ya vendido
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);  // copia del precio del producto al crear 
            $table->decimal('subtotal', 10, 2);    // quantity × unit_price
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
