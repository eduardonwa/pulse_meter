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

        if (! Storage::disk('local')->exists($path)) {
            $this->traffic = [];

            return;
        }

        $this->traffic = Storage::disk('local')->exists($path)
            ? json_decode(Storage::disk('local')->get($path), true) ?? []
            : [];
    }

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

    public function getPaginatedSessionsProperty(): array
    {
        $sessions = $this->traffic['sessions'] ?? [];

        return array_slice(
            $sessions,
            ($this->sessionsPage - 1) * $this->sessionsPerPage,
            $this->sessionsPerPage
        );
    }

    public function getTotalSessionPages(): int
    {
        $total = count($this->traffic['sessions'] ?? []);

        return max(1, (int) ceil($total / $this->sessionsPerPage));
    }
}