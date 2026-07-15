<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasTrafficSessionFilters;
use App\Filament\Pages\Concerns\NavigatesTrafficSessionDays;
use App\Filament\Pages\Concerns\PaginatesTrafficSessions;
use App\Filament\Pages\Concerns\PresentsTrafficSessions;
use App\Services\Traffic\TrafficSummaryReader;
use App\Services\Traffic\TrafficSessionCorrelator;
use App\Services\Traffic\ProductEventSessionReader;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

class TrafficAnalytics extends Page
{
    use HasTrafficSessionFilters;
    use NavigatesTrafficSessionDays;
    use PaginatesTrafficSessions;
    use PresentsTrafficSessions;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Traffic';

    protected static ?string $title = 'Traffic Analytics';

    protected static string | \UnitEnum | null $navigationGroup =
        'Analytics';

    protected ?string $subheading =
        'Sessions grouped by IP, User-Agent and time window.';
     
    protected string $view = 'filament.pages.traffic-analytics';

    protected Width|string|null $maxContentWidth = Width::ScreenTwoExtraLarge;
    
    protected ?array $trafficSummaryCache = null;
    
    #[Computed]
    public function traffic(): array
    {
        return $this->trafficSummaryCache ??=
            app(TrafficSummaryReader::class)->read();
    }

    #[Computed]
    public function productEventSessions(): array
    {
        return app(ProductEventSessionReader::class)->read(
            $this->selectedSessionDate,
            Auth::user()
        );
    }

    #[Computed]
    public function paginatedCorrelatedSessions(): array
    {
        $requestSessions =
            $this->getPaginatedSessionsProperty();

        $productSessions =
            app(ProductEventSessionReader::class)->read(
                $this->selectedSessionDate,
                Auth::user()
            );

        return app(TrafficSessionCorrelator::class)->correlate(
            $requestSessions,
            $productSessions
        );
    }

    public function mount(): void
    {
        $this->restoreSessionTypeFilters();
        $this->syncSelectedSessionDate();
    }

    public function getAvailableSessionDates(): array
    {
        $requestDates = collect($this->getFilteredSessions())
            ->map(
                fn (array $session): ?string =>
                    $this->getSessionDateKey($session)
            )
            ->filter();

        $productEventDates = app(ProductEventSessionReader::class)
            ->availableDates(Auth::user());

        return $requestDates
            ->merge($productEventDates)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }
}