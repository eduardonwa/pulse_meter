<?php

namespace App\Filament\Pages\Concerns;

use App\Services\UserDateFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

trait NavigatesTrafficSessionDays
{
    public ?string $selectedSessionDate = null;

    public function previousSessionDay(): void
    {
        $dates = $this->getAvailableSessionDates();
        $index = array_search(
            $this->selectedSessionDate,
            $dates,
            true
        );

        if ($index === false || $index >= count($dates) - 1) {
            return;
        }

        $this->selectedSessionDate = $dates[$index + 1];
        $this->resetSessionsPagination();
    }

    public function nextSessionDay(): void
    {
        $dates = $this->getAvailableSessionDates();
        $index = array_search(
            $this->selectedSessionDate,
            $dates,
            true
        );

        if ($index === false || $index <= 0) {
            return;
        }

        $this->selectedSessionDate = $dates[$index - 1];
        $this->resetSessionsPagination();
    }

    public function canGoToPreviousSessionDay(): bool
    {
        $dates = $this->getAvailableSessionDates();
        $index = array_search(
            $this->selectedSessionDate,
            $dates,
            true
        );

        return $index !== false
            && $index < count($dates) - 1;
    }

    public function canGoToNextSessionDay(): bool
    {
        $dates = $this->getAvailableSessionDates();
        $index = array_search(
            $this->selectedSessionDate,
            $dates,
            true
        );

        return $index !== false && $index > 0;
    }

    public function getSelectedDateSessionsProperty(): array
    {
        if ($this->selectedSessionDate === null) {
            return [];
        }

        return collect($this->getFilteredSessions())
            ->filter(
                fn (array $session): bool =>
                    $this->getSessionDateKey($session)
                    === $this->selectedSessionDate
            )
            ->values()
            ->all();
    }

    public function getSelectedDateSessionsCountProperty(): int
    {
        return count($this->getSelectedDateSessionsProperty());
    }

    public function getSelectedSessionDateLabelProperty(): string
    {
        $session = $this->getSelectedDateSessionsProperty()[0] ?? null;

        if ($session === null) { return 'No date'; }

        return UserDateFormatter::dateTimeParts(
            $session['last_seen']
                ?? $session['last_seen_timestamp']
                ?? null,
            Auth::user()
        )['date'];
    }

    public function getAvailableSessionDatesProperty(): array
    {
        return $this->getAvailableSessionDates();
    }

    public function getAvailableSessionDates(): array
    {
        return collect($this->getFilteredSessions())
            ->map(
                fn (array $session): ?string =>
                    $this->getSessionDateKey($session)
            )
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    protected function syncSelectedSessionDate(): void
    {
        $dates = $this->getAvailableSessionDates();

        if ($dates === []) {
            $this->selectedSessionDate = null;

            return;
        }

        if (
            $this->selectedSessionDate === null
            || ! in_array($this->selectedSessionDate, $dates, true)
        ) {
            $this->selectedSessionDate = $dates[0];
        }
    }

    protected function getSessionDateKey(array $session): ?string
    {
        $timezone = UserDateFormatter::timezone(Auth::user());

        if (! empty($session['last_seen_timestamp'])) {
            return Carbon::createFromTimestamp(
                (int) $session['last_seen_timestamp']
            )
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