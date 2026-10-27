<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Administración' }} · OMNILIFE Demo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark" x-data="{ abierto: false }">
        <div class="container-fluid px-lg-4">
            <span class="navbar-brand marca">OMNILIFE <span class="text-acento">· Demo</span> <small class="fw-normal fs-6 text-white-50">Admin</small></span>
            <button class="navbar-toggler" type="button" aria-label="Abrir menú" @click="abierto = !abierto">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" :class="{ 'show': abierto }">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('tienda.catalogo') }}"><i class="bi bi-shop"></i> Ver tienda</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 py-4">
        <div class="container-fluid px-lg-4">
            {{ $slot }}
        </div>
    </main>

    <footer class="border-top py-3 text-body-secondary">
        <div class="container-fluid px-lg-4">
            <x-aviso-demo />
        </div>
    </footer>

    @livewireScripts
</body>
</html>
