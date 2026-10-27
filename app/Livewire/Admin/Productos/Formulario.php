<?php

namespace App\Livewire\Admin\Productos;

use App\Exceptions\TipoCambioNoDisponible;
use App\Livewire\Forms\ProductoForm;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\TipoCambioService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Formulario extends Component
{
    use WithFileUploads;

    public ProductoForm $form;

    /** @var array{valor: float, fecha: string, fuente: string}|null */
    public ?array $tipoCambio = null;

    public function mount(?Producto $producto = null): void
    {
        if ($producto?->exists) {
            $this->form->setProducto($producto);
        }
    }

    public function recalcularMxn(TipoCambioService $servicio): void
    {
        $this->form->validateOnly('precio_usd');

        try {
            $this->tipoCambio = $servicio->obtener();
        } catch (TipoCambioNoDisponible) {
            $this->dispatch('toast', mensaje: 'No se pudo obtener el tipo de cambio: captura el precio MXN manualmente.', tipo: 'warning');

            return;
        }

        $this->form->precio_mxn = number_format($this->form->precio_usd * $this->tipoCambio['valor'], 2, '.', '');
        $this->form->validateOnly('precio_mxn');
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
