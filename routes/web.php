<?php

use App\Http\Controllers\Auth\CerrarSesionController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Login;
use App\Livewire\Tienda\Catalogo;
use Illuminate\Support\Facades\Route;

Route::get('/', Catalogo::class)->name('tienda.catalogo');

Route::get('/admin/login', Login::class)->middleware('guest')->name('login');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::post('/logout', CerrarSesionController::class)->name('logout');
});
