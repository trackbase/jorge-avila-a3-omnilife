<?php

namespace App\Livewire\Admin;

use App\Exceptions\TipoCambioNoDisponible;
use App\Services\ReporteService;
use App\Services\TipoCambioService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Panel')]
class Dashboard extends Component
{
    #[Url(except: '')]
    public string $desde = '';

    #[Url(except: '')]
    public string $hasta = '';

    #[Url(except: 'mes')]
    public string $agrupacion = 'mes';

    protected ReporteService $reportes;

    public function boot(ReporteService $reportes): void
    {
        $this->reportes = $reportes;
    }

    public function mount(): void
    {
        $this->desde = $this->desde ?: now()->subMonthsNoOverflow(5)->startOfMonth()->toDateString();
        $this->hasta = $this->hasta ?: today()->toDateString();
    }

    /** La gráfica vive en un wire:ignore: Livewire no la re-renderiza, Alpine la actualiza con este evento. */
    public function updated(): void
    {
        $this->dispatch('grafica-actualizada', datos: $this->datosGrafica());
    }

    public function render(TipoCambioService $tipoCambio)
    {
        [$desde, $hasta] = $this->rango();

        try {
            $tc = $tipoCambio->obtener();
        } catch (TipoCambioNoDisponible) {
            $tc = null;
        }

        return view('livewire.admin.dashboard', [
            'resumen' => $this->reportes->resumen($desde, $hasta),
            'top' => $this->reportes->topProductos($desde, $hasta),
            'grafica' => $this->datosGrafica(),
            'tipoCambio' => $tc,
        ]);
    }

    private function datosGrafica(): array
    {
        [$desde, $hasta] = $this->rango();

        return $this->reportes->gananciasPorPeriodo($desde, $hasta, $this->agrupacion === 'semana' ? 'semana' : 'mes');
    }

    private function rango(): array
    {
        [$desde, $hasta] = $this->reportes->rango($this->desde, $this->hasta);

        return [
            $desde ?? now()->subMonthsNoOverflow(5)->startOfMonth(),
            $hasta ?? today()->endOfDay(),
        ];
    }
}
