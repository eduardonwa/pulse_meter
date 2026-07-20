<?php

namespace Tests\Feature;

use App\Filament\Pages\TrafficAnalytics;
use App\Services\Traffic\ProductEventSessionReader;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class TrafficAnalyticsNavigationTest extends TestCase
{
    public function test_can_navigate_to_previous_day_when_selected_day_is_empty(): void
    {
        Storage::fake('local');

        $this->mock(
            ProductEventSessionReader::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('availableDates')
                    ->andReturn(['2026-07-19']);
            }
        );

        $page = app(TrafficAnalytics::class);

        // Día actual vacío.
        $page->selectedSessionDate = '2026-07-20';

        $this->assertSame(
            ['2026-07-20', '2026-07-19'],
            $page->getAvailableSessionDates()
        );

        $this->assertTrue(
            $page->canGoToPreviousSessionDay()
        );
    }
}