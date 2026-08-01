<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class RoutinesDialog extends Component
{
    public ?array $routine = null;

    public array $routines = [];

    public bool $usesServerPersistence = false;

    public function mount(
        ?array $routine = null,
        array $routines = [],
        bool $usesServerPersistence = false,
    ): void {
        /*
         * Al modal no le hace falta recibir todos los steps
         * de la rutina activa.
         */
        $this->routine = $routine
            ? [
                'id' => $routine['id'],
                'name' => $routine['name'],
                'position' => $routine['position'],
                'is_default' => $routine['is_default'],
            ]
            : null;

        $this->routines = array_values($routines);

        $this->usesServerPersistence =
            $usesServerPersistence;
    }

    public function render(): View
    {
        return view('livewire.routines-dialog');
    }
}