<?php

use App\Livewire\Admin\Login;
use App\Livewire\Tienda\Catalogo;
use Illuminate\Support\Facades\Route;

Route::get('/', Catalogo::class)->name('tienda.catalogo');

Route::get('/admin/login', Login::class)->middleware('guest')->name('login');
