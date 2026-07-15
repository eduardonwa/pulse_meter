<x-filament-panels::page>
    @php
        $traffic = $this->traffic;
        $summary = $traffic['summary'] ?? [];

        $generatedAtParts = \App\Services\UserDateFormatter::dateTimeParts(
            $traffic['generated_at'] ?? null,
            auth()->user()
        );

        $cards = [
            [
                'key' => 'human_like',
                'label' => 'Human (likely)',
                'description' => 'Loaded the homepage and recognized app assets.',
            ],
            [
                'key' => 'scanner',
                'label' => 'Scanners',
                'description' => 'Requested sensitive paths or were blocked.',
            ],
            [
                'key' => 'internal',
                'label' => 'Internal',
                'description' => 'Internal IPs and local tests.',
            ],
            [
                'key' => 'unknown',
                'label' => 'Unknown',
                'description' => 'Not enough evidence.',
            ],
        ];
    @endphp

    @if (empty($traffic))
        <section aria-labelledby="empty-traffic-title">
            <header>
                <h1 id="empty-traffic-title">
                    No traffic summary found yet
                </h1>

                <p>
                    Run the following command to generate the traffic summary:
                </p>
            </header>

            <pre><code>php artisan traffic:generate-summary</code></pre>
        </section>
    @else
        <div class="traffic-analysis">
            @include('filament.pages.traffic-analysis.snapshot')
            
            @include('filament.pages.traffic-analysis.breakdown')

            <section class="sessions" aria-labelledby="sessions-title">
                <header class="sessions__header">
                    <h2 class="title" id="title">Sessions</h2>
                    <p class="description"> Interpreted visits with server requests and product activity. </p>
                </header>
    
                @include('filament.pages.traffic-analysis.session.controls')
    
                @include('filament.pages.traffic-analysis.session.results')
            </section>
        </div>
    @endif
</x-filament-panels::page>