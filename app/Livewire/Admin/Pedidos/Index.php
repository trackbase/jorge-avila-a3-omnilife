<?php

namespace App\Livewire\Admin\Pedidos;

use App\Enums\EstatusPedido;
use App\Models\Pedido;
use App\Services\ReporteService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Pedidos')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $desde = '';

    #[Url(except: '')]
    public string $hasta = '';

    #[Locked]
    public ?int $detalleId = null;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function limpiarFechas(): void
    {
        $this->reset('desde', 'hasta');
        $this->resetPage();
    }

    public function verDetalle(int $id): void
    {
        $this->detalleId = Pedido::findOrFail($id)->id;
        $this->dispatch('abrir-detalle');
    }

    public function render(ReporteService $reportes)
    {
        [$desde, $hasta] = $reportes->rango($this->desde, $this->hasta);

        $consulta = Pedido::query()
            ->when($desde, fn ($query) => $query->where('fecha', '>=', $desde))
            ->when($hasta, fn ($query) => $query->where('fecha', '<=', $hasta));

        return view('livewire.admin.pedidos.index', [
            'pedidos' => (clone $consulta)->latest('fecha')->latest('id')->paginate(15),
            'totalPagado' => (clone $consulta)->where('estatus', EstatusPedido::Pagado)->sum('total'),
            'detalle' => $this->detalleId ? Pedido::with('detalles')->find($this->detalleId) : null,
        ]);
    }
}
