<?php

namespace App\Livewire\Tienda;

use App\Exceptions\PedidoNoConfirmado;
use App\Models\Pedido;
use App\Services\CarritoService;
use App\Services\PedidoService;
use Closure;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pagar')]
class Checkout extends Component
{
    #[Validate]
    public string $correo = '';

    #[Validate]
    public string $titular = '';

    #[Validate]
    public string $numeroTarjeta = '';

    #[Validate]
    public string $vencimiento = '';

    #[Validate]
    public string $cvv = '';

    #[Locked]
    public ?int $pedidoId = null;

    protected CarritoService $carrito;

    public function boot(CarritoService $carrito): void
    {
        $this->carrito = $carrito;
    }

    public function mount(): void
    {
        if ($this->carrito->contar() === 0) {
            $this->redirectRoute('tienda.carrito');
        }
    }

    public function rules(): array
    {
        return [
            'correo' => ['required', 'email', 'max:255'],
            'titular' => ['required', 'string', 'max:80'],
            'numeroTarjeta' => ['required', 'digits_between:13,19'],
            'vencimiento' => ['bail', 'required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/', function (string $atributo, string $valor, Closure $fallar) {
                if (Carbon::createFromFormat('!m/y', $valor)->endOfMonth()->isPast()) {
                    $fallar('La tarjeta está vencida.');
                }
            }],
            'cvv' => ['required', 'digits_between:3,4'],
        ];
    }

    public function messages(): array
    {
        return ['vencimiento.regex' => 'Usa el formato MM/AA.'];
    }

    public function pagar(PedidoService $pedidos): void
    {
        $this->validate();

        try {
            $this->pedidoId = $pedidos->confirmar($this->correo, $this->numeroTarjeta)->id;
            $this->dispatch('carrito-actualizado');
        } catch (PedidoNoConfirmado $e) {
            $this->addError('pago', $e->getMessage());
        } finally {
            // Los datos de la tarjeta no se conservan ni en el componente.
            $this->reset('numeroTarjeta', 'cvv');
        }
    }

    public function render()
    {
        if ($this->pedidoId) {
            return view('livewire.tienda.checkout-confirmado', [
                'pedido' => Pedido::with('detalles')->findOrFail($this->pedidoId),
            ])->title('Pedido confirmado');
        }

        $lineas = $this->carrito->lineas();

        return view('livewire.tienda.checkout', [
            'lineas' => $lineas,
            'totales' => $this->carrito->totales($lineas),
        ]);
    }
}
