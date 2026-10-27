<?php

namespace App\Livewire\Forms;

use App\Models\Producto;
use App\Rules\SinAcentosNiEspeciales;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProductoForm extends Form
{
    public ?Producto $producto = null;

    // #[Validate] sin argumentos: valida cada campo al actualizarse usando las reglas de rules().
    #[Validate]
    public $categoria_id = '';

    #[Validate]
    public string $sku = '';

    #[Validate]
    public string $nombre = '';

    #[Validate]
    public string $descripcion_corta = '';

    #[Validate]
    public string $descripcion_larga = '';

    #[Validate]
    public $precio_usd = '';

    #[Validate]
    public $precio_mxn = '';

    #[Validate]
    public $stock = '';

    #[Validate]
    public string $fecha_vigencia = '';

    #[Validate]
    public bool $activo = true;

    #[Validate]
    public $imagen = null;

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            // Rule::unique consulta la tabla completa: un SKU eliminado (soft delete) tampoco se reutiliza.
            'sku' => ['required', 'string', 'max:30', Rule::unique('productos', 'sku')->ignore($this->producto?->id)],
            'nombre' => ['required', 'string', 'max:120', new SinAcentosNiEspeciales],
            'descripcion_corta' => ['required', 'string', 'max:255'],
            'descripcion_larga' => ['required', 'string', 'max:5000'],
            'precio_usd' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'precio_mxn' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'fecha_vigencia' => ['required', 'date'],
            'activo' => ['boolean'],
            'imagen' => [$this->producto ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function setProducto(Producto $producto): void
    {
        $this->producto = $producto;

        $this->fill($producto->only([
            'categoria_id', 'sku', 'nombre', 'descripcion_corta', 'descripcion_larga',
            'precio_usd', 'precio_mxn', 'stock', 'activo',
        ]));
        $this->fecha_vigencia = $producto->fecha_vigencia->toDateString();
    }

    public function guardar(): Producto
    {
        $datos = $this->validate();
        unset($datos['imagen']);

        if ($this->imagen) {
            $anterior = $this->producto?->imagen;
            $datos['imagen'] = $this->imagen->store('productos', 'public');
        }

        $producto = $this->producto ?? new Producto;
        $producto->fill($datos)->save();

        if (isset($anterior)) {
            Storage::disk('public')->delete($anterior);
        }

        return $producto;
    }
}
