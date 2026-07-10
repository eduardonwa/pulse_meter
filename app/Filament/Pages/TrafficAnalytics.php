<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrafficAnalytics extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Traffic';

    protected static ?string $title = 'Traffic Analytics';

    protected static string | \UnitEnum | null $navigationGroup = 'Analytics';

    protected string $view = 'filament.pages.traffic-analytics';

    public array $traffic = [];

    public ?string $selectedSessionDate = null;

    public int $sessionsPage = 1;

    public int $sessionsPerPage = 5;
    
    public function mount(): void
    {
        $path = 'traffic/traffic-summary.json';

        $exists = Storage::disk('local')->exists($path);

        $this->traffic = $exists
            ? json_decode(Storage::disk('local')->get($path), true) ?? []
            : [];

        $savedFilters = session('traffic.session_type_filters', []);

        $this->sessionTypeFilters = array_replace(
            $this->sessionTypeFilters,
            is_array($savedFilters) ? $savedFilters : []
        );

        if (! $exists) {
            $this->traffic = [];

            return;
        }

        $this->syncSelectedSessionDate();
    }

    public array $sessionTypeFilters = [
        'human_probable' => true,
        'scanner' => true,
        'suspicious' => true,
        'internal' => true,
        'admin_activity' => true,
        'unknown' => true,
    ];

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

    public function getTotalSessionPages(): int
    {
        $total = count($this->selectedDateSessions);

        return max(1, (int) ceil($total / $this->sessionsPerPage));
    }

    public function getPaginatedSessionsProperty(): array
    {
        $sessions = $this->selectedDateSessions;

        return array_slice(
            $sessions,
            ($this->sessionsPage - 1) * $this->sessionsPerPage,
            $this->sessionsPerPage
        );
    }

    public function nextSessionsPage(): void
    {
        if ($this->sessionsPage < $this->getTotalSessionPages()) {
            $this->sessionsPage++;
        }
    }

    public function previousSessionsPage(): void
    {
        if ($this->sessionsPage > 1) {
            $this->sessionsPage--;
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
        $this->syncSelectedSessionDate();
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
        $this->syncSelectedSessionDate();
    }

    public function previousSessionDay(): void
    {
        $dates = $this->getAvailableSessionDates();

        $index = array_search($this->selectedSessionDate, $dates, true);

        if ($index === false || $index >= count($dates) - 1) {
            return;
        }

        $this->selectedSessionDate = $dates[$index + 1];
        $this->sessionsPage = 1;
    }

    public function nextSessionDay(): void
    {
        $dates = $this->getAvailableSessionDates();

        $index = array_search($this->selectedSessionDate, $dates, true);

        if ($index === false || $index <= 0) {
            return;
        }

        $this->selectedSessionDate = $dates[$index - 1];
        $this->sessionsPage = 1;
    }

    public function canGoToPreviousSessionDay(): bool
    {
        $dates = $this->getAvailableSessionDates();

        $index = array_search($this->selectedSessionDate, $dates, true);

        return $index !== false && $index < count($dates) - 1;
    }

    public function canGoToNextSessionDay(): bool
    {
        $dates = $this->getAvailableSessionDates();

        $index = array_search($this->selectedSessionDate, $dates, true);

        return $index !== false && $index > 0;
    }

    public function getSelectedSessionDateLabelProperty(): string
    {
        $session = $this->selectedDateSessions[0] ?? null;

        if ($session === null) {
            return 'No date';
        }

        return \App\Services\UserDateFormatter::dateTimeParts(
            $session['last_seen'] ?? $session['last_seen_timestamp'] ?? null,
            Auth::user()
        )['date'];
    }

    public function getSelectedDateSessionsProperty(): array
    {
        if ($this->selectedSessionDate === null) {
            return [];
        }

        return collect($this->getFilteredSessions())
            ->filter(fn ($session) => $this->getSessionDateKey($session) === $this->selectedSessionDate)
            ->values()
            ->all();
    }

    public function getSelectedDateSessionsCountProperty(): int
    {
        return count($this->selectedDateSessions);
    }

    public function getAvailableSessionDatesProperty(): array
    {
        return $this->getAvailableSessionDates();
    }

    public function getAvailableSessionDates(): array
    {
        return collect($this->getFilteredSessions())
            ->map(fn ($session) => $this->getSessionDateKey($session))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function syncSelectedSessionDate(): void
    {
        $dates = $this->getAvailableSessionDates();

        if (empty($dates)) {
            $this->selectedSessionDate = null;

            return;
        }

        if ($this->selectedSessionDate === null || ! in_array($this->selectedSessionDate, $dates, true)) {
            $this->selectedSessionDate = $dates[0];
        }
    }

    private function getSessionDateKey(array $session): ?string
    {
        $timezone = Auth::user()?->timezone ?: config('app.timezone');

        if (! empty($session['last_seen_timestamp'])) {
            return Carbon::createFromTimestamp((int) $session['last_seen_timestamp'])
                ->timezone($timezone)
                ->toDateString();
        }

        if (! empty($session['last_seen'])) {
            return Carbon::parse($session['last_seen'])
                ->timezone($timezone)
                ->toDateString();
        }

        return null;
    }
}