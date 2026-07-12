<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasTrafficSessionFilters;
use App\Filament\Pages\Concerns\NavigatesTrafficSessionDays;
use App\Filament\Pages\Concerns\PaginatesTrafficSessions;
use App\Filament\Pages\Concerns\PresentsTrafficSessions;
use App\Services\Traffic\TrafficSummaryReader;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

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

    protected string $view = 'filament.pages.traffic-analytics';
    
    protected ?array $trafficSummaryCache = null;
    
    #[Computed]
    public function traffic(): array
    {
        return $this->trafficSummaryCache ??=
            app(TrafficSummaryReader::class)->read();
    }

    public function mount(): void
    {
        $this->restoreSessionTypeFilters();
        $this->syncSelectedSessionDate();
    }
}