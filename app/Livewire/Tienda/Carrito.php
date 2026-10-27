<?php

namespace App\Livewire\Tienda;

use App\Services\CarritoService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Carrito')]
class Carrito extends Component
{
    protected CarritoService $carrito;

    public function boot(CarritoService $carrito): void
    {
        $this->carrito = $carrito;
    }

    public function actualizar(int $productoId, int $cantidad): void
    {
        $final = $this->carrito->actualizar($productoId, $cantidad);

        if ($final < $cantidad) {
            $this->dispatch('toast', mensaje: "Solo hay {$final} disponibles de ese producto.", tipo: 'warning');
        }

        $this->dispatch('carrito-actualizado');
    }

    public function quitar(int $productoId): void
    {
        $this->carrito->quitar($productoId);

        $this->dispatch('carrito-actualizado');
        $this->dispatch('toast', mensaje: 'Producto quitado del carrito.', tipo: 'secondary');
    }

    public function render()
    {
        $lineas = $this->carrito->lineas();

        return view('livewire.tienda.carrito', [
            'lineas' => $lineas,
            'totales' => $this->carrito->totales($lineas),
        ]);
    }
}
