<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductoSeeder extends Seeder
{
    /** Tipo de cambio de referencia para los datos de prueba (no se consulta el webservice al sembrar). */
    public const TIPO_CAMBIO = 18.30;

    /** [categoría, nombre, precio USD, stock, estado especial] — productos ficticios. */
    private const PRODUCTOS = [
        ['nutricion', 'Batido Nutricional Vainilla', 24.90, 60],
        ['nutricion', 'Batido Nutricional Chocolate', 24.90, 45],
        ['nutricion', 'Barra Nutritiva Cacao', 3.50, 120],
        ['nutricion', 'Avena Fortificada Canela', 9.80, 35],
        ['nutricion', 'Mezcla de Fibra Natural', 14.20, 0],
        ['nutricion', 'Omega 3 Plus', 21.00, 25],
        ['bebidas', 'Bebida Hidratante Naranja', 12.40, 80],
        ['bebidas', 'Tisana Herbal Manzanilla', 8.90, 50],
        ['bebidas', 'Concentrado de Jamaica', 7.60, 40],
        ['bebidas', 'Polvo para Bebida Mango', 11.30, 30, 'inactivo'],
        ['bebidas', 'Agua Saborizada Fresa', 4.20, 0],
        ['bebidas', 'Chocolate Caliente Light', 10.50, 18],
        ['vitaminas', 'Complejo B Plus', 16.70, 55],
        ['vitaminas', 'Vitamina C 1000', 13.90, 70],
        ['vitaminas', 'Calcio con Vitamina D', 15.40, 22],
        ['vitaminas', 'Magnesio Plus', 12.80, 3],
        ['vitaminas', 'Zinc y Selenio', 11.60, 28, 'vencido'],
        ['vitaminas', 'Hierro Natural', 10.90, 15],
        ['cuidado', 'Crema Hidratante Facial', 27.50, 20],
        ['cuidado', 'Gel Limpiador Suave', 15.80, 32],
        ['cuidado', 'Shampoo Herbal', 12.30, 44],
        ['cuidado', 'Crema Corporal Aloe', 14.60, 26, 'eliminado'],
        ['cuidado', 'Protector Solar FPS 50', 19.90, 38],
        ['cuidado', 'Exfoliante Corporal Avena', 13.20, 12, 'inactivo'],
        ['bienestar', 'Suplemento Articular Flex', 32.00, 16],
        ['bienestar', 'Relajante Nocturno Lavanda', 18.40, 24],
        ['bienestar', 'Fibra Digestiva Plus', 17.10, 30],
        ['bienestar', 'Ginseng Vital', 22.80, 2],
        ['bienestar', 'Aceite de Coco Virgen', 14.90, 36, 'vencido'],
        ['bienestar', 'Cacao Puro en Polvo', 9.40, 48],
    ];

    private const LINEAS = [
        'nutricion' => ['slug' => 'nutricion', 'color' => '#66308C', 'beneficio' => 'nutrición práctica para acompañar tu día'],
        'bebidas' => ['slug' => 'bebidas', 'color' => '#8E4FB8', 'beneficio' => 'hidratación con sabor, lista en segundos'],
        'vitaminas' => ['slug' => 'vitaminas-y-minerales', 'color' => '#4A2266', 'beneficio' => 'apoyo diario con vitaminas y minerales'],
        'cuidado' => ['slug' => 'cuidado-personal', 'color' => '#A0577A', 'beneficio' => 'cuidado diario para tu piel y cabello'],
        'bienestar' => ['slug' => 'bienestar', 'color' => '#5C4B9E', 'beneficio' => 'pequeños hábitos para sentirte mejor'],
    ];

    public function run(): void
    {
        Storage::disk('public')->deleteDirectory('productos');
        $categorias = Categoria::all()->keyBy('slug');
        $consecutivos = [];

        foreach (self::PRODUCTOS as $fila) {
            [$linea, $nombre, $precioUsd, $stock] = $fila;
            $estado = $fila[4] ?? null;
            $datos = self::LINEAS[$linea];
            $categoria = $categorias[$datos['slug']];

            $prefijo = Str::upper(Str::substr($linea, 0, 3));
            $consecutivos[$prefijo] = ($consecutivos[$prefijo] ?? 0) + 1;
            $sku = sprintf('OMN-%s-%03d', $prefijo, $consecutivos[$prefijo]);

            $producto = Producto::create([
                'categoria_id' => $categoria->id,
                'sku' => $sku,
                'nombre' => $nombre,
                'descripcion_corta' => "{$nombre}: {$datos['beneficio']}.",
                'descripcion_larga' => "{$nombre} forma parte de la línea {$categoria->nombre} de esta tienda demostrativa. "
                    ."Pensado para {$datos['beneficio']}, con una presentación práctica para llevar a donde vayas. "
                    .'Producto ficticio creado para la evaluación técnica: la información, el precio y la imagen no corresponden a productos reales.',
                'precio_usd' => $precioUsd,
                'precio_mxn' => round($precioUsd * self::TIPO_CAMBIO, 2),
                'imagen' => $this->generarImagen($sku, $nombre, $datos['color']),
                'stock' => $stock,
                'fecha_vigencia' => $estado === 'vencido' ? now()->subDays(15) : now()->addMonths(fake()->numberBetween(3, 12)),
                'activo' => $estado !== 'inactivo',
            ]);

            if ($estado === 'eliminado') {
                $producto->delete();
            }
        }
    }

    /** Imagen de marcador en SVG generada localmente: sin descargas ni imágenes reales. */
    private function generarImagen(string $sku, string $nombre, string $color): string
    {
        $iniciales = collect(explode(' ', $nombre))->take(2)->map(fn ($palabra) => Str::upper($palabra[0]))->implode('');
        $nombre = e($nombre);

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">
          <defs>
            <linearGradient id="fondo" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$color}"/>
              <stop offset="1" stop-color="#1F1A1F"/>
            </linearGradient>
          </defs>
          <rect width="600" height="600" fill="url(#fondo)"/>
          <circle cx="300" cy="250" r="140" fill="#FFFFFF" fill-opacity="0.12"/>
          <text x="300" y="292" font-family="Montserrat, Arial, sans-serif" font-size="120" font-weight="800" fill="#FFFFFF" text-anchor="middle">{$iniciales}</text>
          <text x="300" y="470" font-family="Arial, sans-serif" font-size="32" font-weight="700" fill="#FFFFFF" text-anchor="middle">{$nombre}</text>
          <text x="300" y="550" font-family="Arial, sans-serif" font-size="20" fill="#C8A5E0" text-anchor="middle" letter-spacing="4">OMNILIFE · DEMO</text>
        </svg>
        SVG;

        $ruta = "productos/{$sku}.svg";
        Storage::disk('public')->put($ruta, $svg);

        return $ruta;
    }
}
