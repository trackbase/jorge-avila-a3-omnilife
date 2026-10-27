<?php

namespace App\Livewire\Tienda;

use App\Services\CarritoService;
use Livewire\Attributes\On;
use Livewire\Component;

class CarritoIcono extends Component
{
    public int $cantidad = 0;

    public function mount(CarritoService $carrito): void
    {
        $this->cantidad = $carrito->contar();
    }

    #[On('carrito-actualizado')]
    public function actualizar(CarritoService $carrito): void
    {
        $this->cantidad = $carrito->contar();
    }

    public function render()
    {
        return view('livewire.tienda.carrito-icono');
    }
}
