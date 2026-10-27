<?php

namespace App\Models;

use App\Enums\EstatusPedido;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio',
        'correo',
        'subtotal',
        'impuestos',
        'total',
        'total_usd',
        'tipo_cambio',
        'estatus',
        'fecha',
        'referencia_pago',
        'tarjeta_ultimos4',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'impuestos' => 'decimal:2',
            'total' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'tipo_cambio' => 'decimal:4',
            'estatus' => EstatusPedido::class,
            'fecha' => 'datetime',
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class);
    }
}
