<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class TrafficAnalytics extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Traffic';

    protected static ?string $title = 'Traffic Analytics';

    protected static string | \UnitEnum | null $navigationGroup = 'Analytics';

    protected string $view = 'filament.pages.traffic-analytics';

    public array $traffic = [];

    public int $sessionsPage = 1;

    public int $sessionsPerPage = 5;
    
    public function mount(): void
    {
        $path = 'traffic/traffic-summary.json';

        $this->traffic = Storage::disk('local')->exists($path)
            ? json_decode(Storage::disk('local')->get($path), true) ?? []
            : [];

        $savedFilters = session('traffic.session_type_filters', []);

        $this->sessionTypeFilters = array_replace(
            $this->sessionTypeFilters,
            is_array($savedFilters) ? $savedFilters : []
        );

        if (! Storage::disk('local')->exists($path)) {
            $this->traffic = [];

            return;
        }
    }

    public array $sessionTypeFilters = [
        'human_probable' => true,
        'scanner' => true,
        'suspicious' => true,
        'internal' => true,
        'admin_activity' => true,
        'unknown' => true,
    ];

    public function nextSessionsPage(): void
    {
        if ($this->sessionsPage < $this->getTotalSessionPages()) {
            $this->sessionsPage++;

            $this->dispatch('traffic-sessions-page-changed');
        }
    }

    public function previousSessionsPage(): void
    {
        if ($this->sessionsPage > 1) {
            $this->sessionsPage--;

            $this->dispatch('traffic-sessions-page-changed');
        }
    }

    public function toggleSessionTypeFilter(string $type): void
    {
        if (! array_key_exists($type, $this->sessionTypeFilters)) {
            return;
        }

        $this->sessionTypeFilters[$type] = ! $this->sessionTypeFilters[$type];

        session([
            'traffic.session_type_filters' => $this->sessionTypeFilters,
        ]);

        $this->sessionsPage = 1;
    }

    public function resetSessionTypeFilters(): void
    {
        $this->sessionTypeFilters = [
            'human_probable' => true,
            'scanner' => true,
            'suspicious' => true,
            'internal' => true,
            'admin_activity' => true,
            'unknown' => true,
        ];

        session([
            'traffic.session_type_filters' => $this->sessionTypeFilters,
        ]);

        $this->sessionsPage = 1;
    }

    public function getPaginatedSessionsProperty(): array
    {
        $sessions = $this->getFilteredSessions();

        return array_slice(
            $sessions,
            ($this->sessionsPage - 1) * $this->sessionsPerPage,
            $this->sessionsPerPage
        );
    }

    public function getTotalSessionPages(): int
    {
        $total = count($this->getFilteredSessions());

        return max(1, (int) ceil($total / $this->sessionsPerPage));
    }

    private function getFilteredSessions(): array
    {
        $sessions = $this->traffic['sessions'] ?? [];

        return collect($sessions)
            ->filter(function ($session) {
                $classification = $session['classification'] ?? 'unknown';

                return $this->sessionTypeFilters[$classification] ?? false;
            })
            ->values()
            ->all();
    }
}