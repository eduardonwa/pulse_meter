<?php

namespace App\Filament\Pages\Concerns;

trait PresentsTrafficSessions
{
    public function classificationLabel(array $session): string
    {
        $classification = $this->normalizeClassification(
            $session['classification'] ?? null
        );

        return match ($classification) {
            'human_like' => 'Human (likely)',
            'scanner' => 'Scanner',
            'internal' => 'Internal',
            default => 'Unknown',
        };
    }
}