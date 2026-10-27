<?php

namespace App\Livewire\Tienda;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\CarritoService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Catálogo')]
class Catalogo extends Component
{
    use WithPagination;

    private const ORDENES = [
        'nombre' => ['nombre', 'asc'],
        'precio-asc' => ['precio_mxn', 'asc'],
        'precio-desc' => ['precio_mxn', 'desc'],
    ];

    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    #[Url(except: '')]
    public string $categoria = '';

    #[Url(as: 'min', except: '')]
    public $precioMin = '';

    #[Url(as: 'max', except: '')]
    public $precioMax = '';

    #[Url(except: '')]
    public string $disponibilidad = '';

    #[Url(except: 'nombre')]
    public string $orden = 'nombre';

    protected CarritoService $carrito;

    public function boot(CarritoService $carrito): void
    {
        $this->carrito = $carrito;
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset('buscar', 'categoria', 'precioMin', 'precioMax', 'disponibilidad', 'orden');
        $this->resetPage();
    }

    public function agregar(int $productoId): void
    {
        $antes = $this->carrito->contar();
        $total = $this->carrito->agregar($productoId);

        $this->dispatch('carrito-actualizado');

        if ($this->carrito->contar() === $antes) {
            $this->dispatch('toast', mensaje: "Ya tienes en tu carrito el máximo disponible ({$total}).", tipo: 'warning');
        } else {
            $this->dispatch('toast', mensaje: "Agregado al carrito ({$total} en total).");
        }
    }

    public function render()
    {
        [$columna, $direccion] = self::ORDENES[$this->orden] ?? self::ORDENES['nombre'];

        $productos = Producto::visibles()
            ->with('categoria')
            ->when($this->buscar !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('nombre', 'like', "%{$this->buscar}%")
                ->orWhere('sku', 'like', "%{$this->buscar}%")))
            ->when($this->categoria !== '', fn ($query) => $query->whereRelation('categoria', 'slug', $this->categoria))
            ->when(is_numeric($this->precioMin), fn ($query) => $query->where('precio_mxn', '>=', $this->precioMin))
            ->when(is_numeric($this->precioMax), fn ($query) => $query->where('precio_mxn', '<=', $this->precioMax))
            ->when($this->disponibilidad === 'disponibles', fn ($query) => $query->where('stock', '>', 0))
            ->when($this->disponibilidad === 'agotados', fn ($query) => $query->where('stock', 0))
            ->orderBy($columna, $direccion)
            ->orderBy('id')
            ->paginate(12);

        $categorias = Categoria::withCount(['productos' => fn ($query) => $query->visibles()])
            ->orderBy('nombre')
            ->get();

        return view('livewire.tienda.catalogo', compact('productos', 'categorias'));
    }
}
