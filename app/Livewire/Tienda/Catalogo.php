<?php

namespace App\Livewire\Tienda;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Catálogo')]
class Catalogo extends Component
{
    public function render()
    {
        return view('livewire.tienda.catalogo');
    }
}
