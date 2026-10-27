<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('sku', 30);
            $table->string('nombre', 120);
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_usd', 12, 2);
            $table->decimal('precio_mxn', 12, 2);
            $table->timestamps();

            $table->unique(['pedido_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_detalle');
    }
};
