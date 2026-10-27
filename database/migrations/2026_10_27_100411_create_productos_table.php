<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('sku', 30)->unique();
            $table->string('nombre', 120);
            $table->string('descripcion_corta', 255);
            $table->text('descripcion_larga');
            $table->decimal('precio_usd', 12, 2);
            $table->decimal('precio_mxn', 12, 2);
            $table->string('imagen');
            $table->unsignedInteger('stock')->default(0);
            $table->date('fecha_vigencia');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activo', 'fecha_vigencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
