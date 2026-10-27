<?php

namespace App\Livewire\Tienda;

use App\Models\Producto;
use App\Services\CarritoService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProductoDetalle extends Component
{
    public Producto $producto;

    public function mount(string $sku): void
    {
        $this->producto = Producto::visibles()->with('categoria')->where('sku', $sku)->firstOrFail();
    }

    public function agregar(CarritoService $carrito, int $cantidad = 1): void
    {
        $total = $carrito->agregar($this->producto->id, $cantidad);

        $this->dispatch('carrito-actualizado');

        if ($total >= $this->producto->stock) {
            $this->dispatch('toast', mensaje: "Tienes en tu carrito el máximo disponible ({$total}).", tipo: 'warning');
        } else {
            $this->dispatch('toast', mensaje: "Agregado al carrito ({$total} en total).");
        }
    }

    public function render()
    {
        return view('livewire.tienda.producto-detalle')->title($this->producto->nombre);
    }
}
