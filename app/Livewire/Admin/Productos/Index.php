<?php

namespace App\Livewire\Admin\Productos;

use App\Models\Producto;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Productos')]
class Index extends Component
{
    use WithPagination;

    private const COLUMNAS_ORDENABLES = ['sku', 'nombre', 'precio_usd', 'precio_mxn', 'stock', 'activo'];

    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    #[Url(except: 'nombre')]
    public string $orden = 'nombre';

    #[Url(except: 'asc')]
    public string $direccion = 'asc';

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function ordenarPor(string $columna): void
    {
        if (! in_array($columna, self::COLUMNAS_ORDENABLES, true)) {
            return;
        }

        $this->direccion = $this->orden === $columna && $this->direccion === 'asc' ? 'desc' : 'asc';
        $this->orden = $columna;
        $this->resetPage();
    }

    public function alternarActivo(int $id): void
    {
        $producto = Producto::findOrFail($id);
        $producto->update(['activo' => ! $producto->activo]);

        $this->dispatch('toast', mensaje: "{$producto->sku} ".($producto->activo ? 'activado' : 'inactivado').'.');
    }

    public function eliminar(int $id): void
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();

        $this->dispatch('toast', mensaje: "{$producto->sku} eliminado.", tipo: 'danger');
    }

    public function render()
    {
        $orden = in_array($this->orden, self::COLUMNAS_ORDENABLES, true) ? $this->orden : 'nombre';
        $direccion = $this->direccion === 'desc' ? 'desc' : 'asc';

        $productos = Producto::query()
            ->when($this->buscar !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('sku', 'like', "%{$this->buscar}%")
                ->orWhere('nombre', 'like', "%{$this->buscar}%")))
            ->orderBy($orden, $direccion)
            ->orderBy('id')
            ->paginate(10);

        return view('livewire.admin.productos.index', compact('productos'));
    }
}
