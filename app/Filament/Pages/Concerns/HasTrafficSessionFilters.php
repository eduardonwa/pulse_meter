<?php

namespace App\Filament\Pages\Concerns;

trait HasTrafficSessionFilters
{
    public array $sessionTypeFilters = [
        'human_like' => true,
        'scanner' => true,
        'internal' => true,
        'unknown' => true,
    ];

    protected function defaultSessionTypeFilters(): array
    {
        return [
            'human_like' => true,
            'scanner' => true,
            'internal' => true,
            'unknown' => true,
        ];
    }

    protected function restoreSessionTypeFilters(): void
    {
        $defaults = $this->defaultSessionTypeFilters();
        $saved = session('traffic.session_type_filters', []);

        if (! is_array($saved)) {
            $saved = [];
        }

        // Evita recuperar filtros antiguos como human_probable.
        $saved = array_intersect_key($saved, $defaults);

        $this->sessionTypeFilters = array_replace(
            $defaults,
            $saved
        );
    }

    protected function getFilteredSessions(): array
    {
        return collect($this->traffic['sessions'] ?? [])
            ->filter(function (array $session): bool {
                $classification = $this->normalizeClassification(
                    $session['classification'] ?? null
                );

                return $this->sessionTypeFilters[$classification] ?? false;
            })
            ->values()
            ->all();
    }

    public function toggleSessionTypeFilter(string $type): void
    {
        if (! array_key_exists($type, $this->sessionTypeFilters)) {
            return;
        }

        $this->sessionTypeFilters[$type] =
            ! $this->sessionTypeFilters[$type];

        $this->persistSessionTypeFilters();
        $this->resetSessionsPagination();
        $this->syncSelectedSessionDate();
    }

    public function resetSessionTypeFilters(): void
    {
        $this->sessionTypeFilters = $this->defaultSessionTypeFilters();

        $this->persistSessionTypeFilters();
        $this->resetSessionsPagination();
        $this->syncSelectedSessionDate();
    }

    protected function persistSessionTypeFilters(): void
    {
        session([
            'traffic.session_type_filters' => $this->sessionTypeFilters,
        ]);
    }

    protected function normalizeClassification(?string $classification): string
    {
        return match ($classification) {
            'human_probable' => 'human_like',
            'suspicious' => 'scanner',
            null => 'unknown',
            default => $classification,
        };
    }
}