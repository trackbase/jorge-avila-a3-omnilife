<?php

use App\Http\Controllers\Auth\CerrarSesionController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Login;
use App\Livewire\Admin\Productos;
use App\Livewire\Tienda\Catalogo;
use Illuminate\Support\Facades\Route;

Route::get('/', Catalogo::class)->name('tienda.catalogo');

Route::get('/admin/login', Login::class)->middleware('guest')->name('login');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::post('/logout', CerrarSesionController::class)->name('logout');

    Route::get('/productos', Productos\Index::class)->name('productos.index');
    Route::get('/productos/nuevo', Productos\Formulario::class)->name('productos.crear');
    Route::get('/productos/{producto}', Productos\Detalle::class)->name('productos.detalle');
    Route::get('/productos/{producto}/editar', Productos\Formulario::class)->name('productos.editar');
});
