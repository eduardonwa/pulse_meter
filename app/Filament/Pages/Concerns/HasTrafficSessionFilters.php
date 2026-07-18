<?php

namespace App\Filament\Pages\Concerns;

trait HasTrafficSessionFilters
{
    abstract public function traffic(): array;
    
    public array $sessionTypeFilters = [
        'browser_like' => true,
        'scanner' => true,
        'automation_suspected' => true,
        'internal' => true,
        'unknown' => true,
    ];

    protected function defaultSessionTypeFilters(): array
    {
        return [
            'browser_like' => true,
            'scanner' => true,
            'automation_suspected' => true,
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

        // Descarta filtros guardados que ya no existen,
        // como human_like o human_probable 
        $saved = array_intersect_key($saved, $defaults);

        $this->sessionTypeFilters = array_replace(
            $defaults,
            $saved
        );
    }

    protected function getFilteredSessions(): array
    {
        $traffic = $this->traffic();

        return collect($traffic['sessions'] ?? [])
            ->filter(function (array $session): bool {
                $classification =
                    $this->normalizeClassification(
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
            'human_probable',
            'human_like' => 'browser_like',
            'suspicious' => 'scanner',
            null => 'unknown',
            default => $classification,
        };
    }
}