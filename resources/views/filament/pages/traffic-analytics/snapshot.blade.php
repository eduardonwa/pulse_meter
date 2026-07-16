<aside class="traffic-snapshot" aria-labelledby="last-update-title">
    <section class="traffic-snapshot__generated-at">
        <h2 class="last-updated-title" id="last-update-title">Last update</h2>

        <time class="traffic-snapshot__timeframe" datetime="{{ $traffic['generated_at'] ?? '' }}">
            <span class="date"> {{ $generatedAtParts['date'] ?? '—' }} </span>
            <span class="time"> {{ $generatedAtParts['time'] ?? '—' }} </span>
            <small class="timezone"> {{ $generatedAtParts['timezone'] ?? '—' }}</small>
        </time>
    </section>

    {{-- traffic overview --}}
    <section class="traffic-snapshot__metrics" aria-labelledby="traffic-overview-title">
        <h2 class="traffic-overview-title" id="traffic-overview-title">Traffic overview</h2>
        
        {{-- metrics --}}
        <dl class="traffic-snapshot__metrics-shell">
            <div class="traffic-snapshot__metric">
                <dt class="label">Requests</dt>
                <dd class="metric">{{ $traffic['total_requests'] ?? 0 }}</dd>
            </div>

            <div class="traffic-snapshot__metric">
                <dt class="label">Sessions</dt>
                <dd class="metric">{{ $traffic['total_sessions'] ?? 0 }}</dd>
            </div>

            <div class="traffic-snapshot__metric">
                <dt class="label">Skipped lines</dt>
                <dd class="metric">{{ $traffic['skipped_lines'] ?? 0 }}</dd>
            </div>

            <div class="traffic-snapshot__metric">
                <dt class="label">Session gap</dt>
                <dd class="metric">
                    {{ $traffic['session_gap_minutes'] ?? 30 }} min
                </dd>
            </div>
        </dl>
    </section>
</aside>