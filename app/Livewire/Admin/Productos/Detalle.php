<?php

namespace App\Livewire\Admin\Productos;

use App\Models\Producto;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Detalle de producto')]
class Detalle extends Component
{
    public Producto $producto;

    public function render()
    {
        return view('livewire.admin.productos.detalle');
    }
}
