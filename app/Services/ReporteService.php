<?php

namespace App\Services;

use App\Enums\EstatusPedido;
use App\Models\Pedido;
use Carbon\CarbonPeriod;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Consultas de reportes. Ganancia (A2) = ventas netas sin IVA (subtotal) de pedidos pagados. */
class ReporteService
{
    /**
     * Convierte las fechas Y-m-d del formulario en un rango [inicio del día, fin del día].
     * Lo vacío o inválido queda en null y un rango invertido se voltea.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function rango(?string $desde, ?string $hasta): array
    {
        $inicio = $this->fecha($desde)?->startOfDay();
        $fin = $this->fecha($hasta)?->endOfDay();

        if ($inicio && $fin && $inicio->gt($fin)) {
            return [$fin->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        return [$inicio, $fin];
    }

    /** @return array{etiquetas: list<string>, valores: list<float>} */
    public function gananciasPorPeriodo(Carbon $desde, Carbon $hasta, string $agrupacion = 'mes'): array
    {
        $porSemana = $agrupacion === 'semana';

        // %x-%v es año y semana ISO en MySQL; equivale a 'o-W' en PHP.
        $ganancias = $this->pagados($desde, $hasta)
            ->selectRaw(($porSemana ? "DATE_FORMAT(fecha, '%x-%v')" : "DATE_FORMAT(fecha, '%Y-%m')").' as periodo, SUM(subtotal) as ganancia')
            ->groupBy('periodo')
            ->pluck('ganancia', 'periodo');

        $periodos = $porSemana
            ? CarbonPeriod::create($desde->copy()->startOfWeek(), '1 week', $hasta)
            : CarbonPeriod::create($desde->copy()->startOfMonth(), '1 month', $hasta);

        $etiquetas = [];
        $valores = [];

        foreach ($periodos as $inicio) {
            $llave = $inicio->format($porSemana ? 'o-W' : 'Y-m');
            $etiquetas[] = $porSemana ? 'Sem '.$inicio->isoFormat('D MMM') : $inicio->isoFormat('MMM YYYY');
            $valores[] = round((float) ($ganancias[$llave] ?? 0), 2);
        }

        return ['etiquetas' => $etiquetas, 'valores' => $valores];
    }

    /**
     * Más vendidos por unidades en pedidos pagados. El join directo a `productos`
     * no aplica el scope de SoftDeletes, así que también cuenta productos eliminados.
     */
    public function topProductos(Carbon $desde, Carbon $hasta, int $limite = 3): Collection
    {
        return DB::table('pedido_detalle')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_detalle.pedido_id')
            ->join('productos', 'productos.id', '=', 'pedido_detalle.producto_id')
            ->where('pedidos.estatus', EstatusPedido::Pagado->value)
            ->whereBetween('pedidos.fecha', [$desde, $hasta])
            ->groupBy('productos.id')
            ->selectRaw('productos.sku, productos.nombre, productos.deleted_at IS NOT NULL as eliminado')
            ->selectRaw('SUM(pedido_detalle.cantidad) as unidades, SUM(pedido_detalle.cantidad * pedido_detalle.precio_mxn) as importe')
            ->orderByDesc('unidades')
            ->orderByDesc('importe')
            ->limit($limite)
            ->get();
    }

    /** @return array{pedidos: int, ganancia: float, ticket_promedio: float} */
    public function resumen(Carbon $desde, Carbon $hasta): array
    {
        $fila = $this->pagados($desde, $hasta)
            ->selectRaw('COUNT(*) as pedidos, COALESCE(SUM(subtotal), 0) as ganancia, COALESCE(AVG(total), 0) as ticket')
            ->first();

        return [
            'pedidos' => (int) $fila->pedidos,
            'ganancia' => round((float) $fila->ganancia, 2),
            'ticket_promedio' => round((float) $fila->ticket, 2),
        ];
    }

    private function pagados(Carbon $desde, Carbon $hasta)
    {
        return Pedido::query()
            ->where('estatus', EstatusPedido::Pagado)
            ->whereBetween('fecha', [$desde, $hasta]);
    }

    /**
     * Se ignoran las fechas imposibles (2026-02-31 se desbordaría a marzo) y los años a medio teclear:
     * el <input type="date"> de Chrome envía 0002, 0020 y 0202, que generarían miles de periodos.
     */
    private function fecha(?string $valor): ?Carbon
    {
        try {
            $fecha = filled($valor) ? Carbon::createFromFormat('!Y-m-d', $valor) : null;
        } catch (InvalidFormatException) {
            return null;
        }

        if (! $fecha || $fecha->format('Y-m-d') !== $valor || $fecha->year < 2000 || $fecha->year > 2100) {
            return null;
        }

        return $fecha;
    }
}
