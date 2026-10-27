<?php

namespace App\Enums;

enum EstatusPedido: string
{
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Rechazado = 'rechazado';
    case Cancelado = 'cancelado';

    public function etiqueta(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Pagado => 'success',
            self::Rechazado => 'danger',
            self::Cancelado => 'secondary',
        };
    }
}
