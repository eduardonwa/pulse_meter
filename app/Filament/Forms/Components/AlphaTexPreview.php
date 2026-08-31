<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

class AlphaTexPreview extends Field
{
    protected string $view =
        'filament.forms.components.alpha-tex-preview';

    protected string $alphaTexField = 'alpha_tex';

    protected string $bpmField = 'bpm';

    protected string $titleField = 'alpha_tex_title';
    protected string $trackField = 'alpha_tex_track';
    protected string $instrumentField = 'alpha_tex_instrument';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }

    public function alphaTexField(string $field): static
    {
        $this->alphaTexField = $field;

        return $this;
    }

    public function bpmField(string $field): static
    {
        $this->bpmField = $field;

        return $this;
    }

    public function getAlphaTexStatePath(): string
    {
        return $this->getSiblingStatePath($this->alphaTexField);
    }

    public function getBpmStatePath(): string
    {
        return $this->getSiblingStatePath($this->bpmField);
    }

    private function getSiblingStatePath(string $field): string
    {
        $statePath = $this->getStatePath();

        if (! str_contains($statePath, '.')) {
            return $field;
        }

        return Str::beforeLast($statePath, '.') . '.' . $field;
    }

    public function titleField(string $field): static
    {
        $this->titleField = $field;

        return $this;
    }

    public function getTitleStatePath(): string
    {
        return $this->getSiblingStatePath($this->titleField);
    }

    public function trackField(string $field): static
    {
        $this->trackField = $field;

        return $this;
    }

    public function getTrackStatePath(): string
    {
        return $this->getSiblingStatePath($this->trackField);
    }

    public function instrumentField(string $field): static
    {
        $this->instrumentField = $field;

        return $this;
    }

    public function getInstrumentStatePath(): string
    {
        return $this->getSiblingStatePath($this->instrumentField);
    }
}
