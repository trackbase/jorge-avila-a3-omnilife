<?php

namespace App\Exceptions;

use RuntimeException;

class TipoCambioNoDisponible extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No se pudo obtener el tipo de cambio USD/MXN y no hay un valor previo guardado.');
    }
}
