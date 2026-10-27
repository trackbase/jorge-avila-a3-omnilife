<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'categoria_id',
        'sku',
        'nombre',
        'descripcion_corta',
        'descripcion_larga',
        'precio_usd',
        'precio_mxn',
        'imagen',
        'stock',
        'fecha_vigencia',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_usd' => 'decimal:2',
            'precio_mxn' => 'decimal:2',
            'stock' => 'integer',
            'fecha_vigencia' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** Lo que la tienda puede mostrar: activo y con vigencia de hoy en adelante (SoftDeletes ya excluye los eliminados). */
    public function scopeVisibles(Builder $query): void
    {
        $query->where('activo', true)->whereDate('fecha_vigencia', '>=', today());
    }
}
