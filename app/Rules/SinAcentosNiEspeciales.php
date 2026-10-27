<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SinAcentosNiEspeciales implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[A-Za-z0-9 \-]+$/', $value)) {
            $fail('El campo :attribute solo admite letras sin acentos, números, espacios y guiones.');
        }
    }
}
