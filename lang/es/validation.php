<?php

// Solo las reglas que usa el proyecto; las demás caen al inglés (APP_FALLBACK_LOCALE).
return [

    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha igual o anterior a :date.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'decimal' => 'El campo :attribute debe tener :decimal decimales.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
    ],
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El :attribute seleccionado no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'file' => 'El campo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'El :attribute ya está registrado.',

    'attributes' => [
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'correo' => 'correo electrónico',
        'sku' => 'SKU',
        'categoria_id' => 'categoría',
        'descripcion_corta' => 'descripción corta',
        'descripcion_larga' => 'descripción larga',
        'precio_usd' => 'precio en USD',
        'precio_mxn' => 'precio en MXN',
        'fecha_vigencia' => 'fecha de vigencia',
        'numeroTarjeta' => 'número de tarjeta',
        'numero_tarjeta' => 'número de tarjeta',
        'cvv' => 'CVV',
    ],

];
