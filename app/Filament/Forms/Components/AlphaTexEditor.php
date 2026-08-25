<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

class AlphaTexEditor extends Field
{
    protected string $view =
        'filament.forms.components.alpha-tex-editor';

    protected string $bpmField = 'bpm';

    public function bpmField(string $field): static
    {
        $this->bpmField = $field;

        return $this;
    }

    public function getBpmStatePath(): string
    {
        $statePath = $this->getStatePath();

        if (! str_contains($statePath, '.')) {
            return $this->bpmField;
        }

        return Str::beforeLast($statePath, '.')
            . '.'
            . $this->bpmField;
    }
}