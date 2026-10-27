<?php

namespace App\Exceptions;

use RuntimeException;

/** El pedido no se pudo pagar; el mensaje es apto para mostrarse al cliente. */
class PedidoNoConfirmado extends RuntimeException
{
}
