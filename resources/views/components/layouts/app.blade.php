<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Tienda' }} · OMNILIFE Demo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark" x-data="{ abierto: false }">
        <div class="container">
            <a class="navbar-brand marca" href="{{ route('tienda.catalogo') }}">OMNILIFE <span class="text-acento">· Demo</span></a>
            <button class="navbar-toggler" type="button" aria-label="Abrir menú" @click="abierto = !abierto">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" :class="{ 'show': abierto }">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs('tienda.catalogo', 'tienda.producto')]) href="{{ route('tienda.catalogo') }}"><i class="bi bi-grid"></i> Catálogo</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <livewire:tienda.carrito-icono />
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 py-4">
        <div class="container">
            {{ $slot }}
        </div>
    </main>

    <footer class="bg-dark text-white-50 py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
            <span class="marca text-white">OMNILIFE <span class="text-acento">· Demo</span></span>
            <x-aviso-demo />
        </div>
    </footer>

    <x-toasts />

    @livewireScripts
</body>
</html>
