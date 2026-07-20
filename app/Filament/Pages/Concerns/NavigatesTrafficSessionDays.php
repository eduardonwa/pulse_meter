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
        if ($this->selectedSessionDate === null) { return 'No date'; }

        $user = Auth::user();
        $timezone = UserDateFormatter::timezone($user);

        $date = Carbon::createFromFormat(
            'Y-m-d',
            $this->selectedSessionDate,
            $timezone
        )->startOfDay();

        return UserDateFormatter::dateTimeParts(
            $date->toIso8601String(),
            $user
        )['date'];
    }

    public function getAvailableSessionDatesProperty(): array
    {
        return $this->getAvailableSessionDates();
    }

    public function getAvailableSessionDates(): array
    {
        $traffic = $this->traffic();

        $dates = collect($traffic['sessions'] ?? [])
            ->map(
                fn (array $session): ?string =>
                    $this->getSessionDateKey($session)
            )
            ->filter();

        if ($this->selectedSessionDate !== null) {
            $dates->push($this->selectedSessionDate);
        }

        return $dates
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    protected function syncSelectedSessionDate(): void
    {
        if ($this->selectedSessionDate !== null) { return; }

        $timezone = UserDateFormatter::timezone(Auth::user());

        $this->selectedSessionDate = Carbon::now($timezone)
            ->toDateString();
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