<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoDetalle extends Model
{
    // El pluralizador de Laravel es en inglés: inferiría "pedido_detalles".
    protected $table = 'pedido_detalle';

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'sku',
        'nombre',
        'cantidad',
        'precio_usd',
        'precio_mxn',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_usd' => 'decimal:2',
            'precio_mxn' => 'decimal:2',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }
}
