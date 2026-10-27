<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->nullable()->unique();
            $table->string('correo');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('impuestos', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('total_usd', 12, 2);
            $table->decimal('tipo_cambio', 10, 4);
            $table->string('estatus', 20)->default('pendiente');
            $table->dateTime('fecha');
            $table->string('referencia_pago', 40)->nullable();
            $table->char('tarjeta_ultimos4', 4)->nullable();
            $table->timestamps();

            $table->index(['estatus', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
