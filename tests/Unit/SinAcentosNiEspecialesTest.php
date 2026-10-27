<?php

namespace Tests\Unit;

use App\Rules\SinAcentosNiEspeciales;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SinAcentosNiEspecialesTest extends TestCase
{
    public static function validos(): array
    {
        return [
            'letras y espacios' => ['Batido Nutricional Vainilla'],
            'con numeros' => ['Omega 3 Plus'],
            'con guion' => ['Gel-Limpiador Suave'],
            'mayusculas' => ['FPS 50'],
        ];
    }

    public static function invalidos(): array
    {
        return [
            'acento' => ['Proteína'],
            'dieresis' => ['Pingüino'],
            'enie (supuesto documentado)' => ['Piña'],
            'signo de exclamacion' => ['Oferta!'],
            'simbolo de moneda' => ['Precio $10'],
            'guion bajo' => ['batido_vainilla'],
            'salto de linea final' => ["Batido\n"],
            'vacio' => [''],
            'no es texto' => [123],
        ];
    }

    #[DataProvider('validos')]
    public function test_acepta_nombres_validos(string $nombre): void
    {
        $this->assertFalse($this->falla($nombre));
    }

    #[DataProvider('invalidos')]
    public function test_rechaza_acentos_y_caracteres_especiales(mixed $nombre): void
    {
        $this->assertTrue($this->falla($nombre));
    }

    private function falla(mixed $valor): bool
    {
        $fallo = false;
        (new SinAcentosNiEspeciales)->validate('nombre', $valor, function () use (&$fallo) {
            $fallo = true;
        });

        return $fallo;
    }
}
