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
            'browser_like' => 'Browser-like',
            'automation_suspected' => 'Automation suspected',
            'scanner' => 'Scanner',
            'internal' => 'Internal',
            default => 'Unknown',
        };
    }
}