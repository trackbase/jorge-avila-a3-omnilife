<?php

namespace App\Livewire\Admin\Productos;

use App\Livewire\Forms\ProductoForm;
use App\Models\Categoria;
use App\Models\Producto;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Formulario extends Component
{
    use WithFileUploads;

    public ProductoForm $form;

    public function mount(?Producto $producto = null): void
    {
        if ($producto?->exists) {
            $this->form->setProducto($producto);
        }
    }

    public function guardar()
    {
        $producto = $this->form->guardar();

        session()->flash('toast', ['mensaje' => "Producto {$producto->sku} guardado."]);

        return $this->redirectRoute('admin.productos.index');
    }

    public function render()
    {
        return view('livewire.admin.productos.formulario', [
            'categorias' => Categoria::orderBy('nombre')->get(),
        ])->title($this->form->producto ? 'Editar producto' : 'Nuevo producto');
    }
}
