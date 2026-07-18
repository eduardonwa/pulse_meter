@props([
    'cards' => [],
    'summary' => [],
])

<header class="traffic-breakdown" aria-labelledby="classification-summary-title">
    <h2 class="traffic-breakdown__title" id="traffic-breakdown-title"> Visit outcome </h2>

    <div class="classification">        
        <div class="classification__stat">
            <dt class="label">Bounce rate</dt>
            <dd class="card-key">{{ $bounceRate === null ? '—' : number_format($bounceRate, 2) . '%' }}</dd>
        </div>

        <div class="classification__stat">
            <dt class="label">Eligible sessions</dt>
            <dd class="card-key">{{ $bounceSummary[ 'eligible_sessions' ] ?? 0 }}</dd>
        </div>

        <div class="classification__stat">
            <dt class="label">Bounced sessions</dt>
            <dd class="card-key">{{ $bounceSummary['bounced_sessions'] ?? 0 }}</dd>
        </div>

        <div class="classification__stat">
            <dt class="label">Single page engaged</dt>
            <dd class="card-key">{{ $bounceSummary['single_page_engaged_sessions'] ?? 0 }}</dd>
        </div>
    </div>
</header>