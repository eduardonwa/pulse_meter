<div class="sessions-results" aria-live="polite">
    @forelse ($this->paginatedCorrelatedSessions as $session)
        @php
            $classification = $session['classification'] ?? 'unknown';
            $risk = $session['risk_level'] ?? 'neutral';
            $paths = $session['paths'] ?? [];
            $visiblePaths = array_slice($paths, 0, 12);
            $hiddenPathsCount = max(0,count($paths) - count($visiblePaths));
            $sensitivePaths = $session['sensitive_paths'] ?? [];
            $firstSeenValue =
                $session['first_seen']
                ?? $session['first_seen_timestamp']
                ?? null;

            $lastSeenValue =
                $session['last_seen']
                ?? $session['last_seen_timestamp']
                ?? null;

            $firstSeenParts =
                \App\Services\UserDateFormatter::dateTimeParts(
                    $firstSeenValue,
                    auth()->user()
                );

            $lastSeenParts =
                \App\Services\UserDateFormatter::dateTimeParts(
                    $lastSeenValue,
                    auth()->user()
                );

            $durationLabel =
                \App\Services\UserDateFormatter::duration(
                    $session['duration_seconds'] ?? 0
                );

            $sessionId =
                'session-' . md5(
                    ($session['ip'] ?? '')
                    . ($firstSeenValue ?? '')
                    . ($session['user_agent'] ?? '')
                );
        @endphp

        {{-- log info breakdown --}}
        <article class="session-entry" aria-labelledby="{{ $sessionId }}" x-data="{ sessionTab: 'requests' }">
            {{-- general info --}}
            <header class="session-entry__header">
                <div class="summary">
                    {{-- tags, visit overview --}}
                    <div class="badges">
                        <div class="badge badge--{{ str($classification)->slug('-') }}">
                            <span>{{ str($classification)->replace('_', ' ')->title() }}</span>
                        </div>

                        <div class="badge badge--{{ str($risk)->slug('-') }}">
                            <span>{{ str($risk)->replace('_', ' ')->title() }}</span>
                        </div>
                    </div>

                    <div class="overview">
                        <span>{{ $session['requests_count'] ?? 0 }} requests</span>    
                        <span>{{ $durationLabel }}</span>
                    </div>
                </div>

                {{-- IP --}}
                <h2 class="ip" id="{{ $sessionId }}">{{ $session['ip'] ?? 'Unknown IP' }}</h2>
                
                {{-- visit type --}}
                <section class="session-entry__request-signals">
                    <div class="signals" aria-labelledby="{{ $sessionId }}-signals">
                        <h2 class="session-entry__section-title" id="{{ $sessionId }}-signals">
                            Request signals
                        </h2>

                        <div class="grid">
                            <div class="signal">
                                <h3 class="label">Home</h3>

                                <p class="signal-value {{
                                    ! empty($session['requested_home'])
                                        ? 'signal-value--success'
                                        : 'signal-value--neutral'
                                    }}"
                                >{{ ! empty($session['requested_home']) ? 'Yes' : 'No' }}</p>
                            </div>

                            <div class="signal">
                                <h3 class="label">Assets</h3>

                                <p class="signal-value {{
                                    ! empty($session['loaded_assets'])
                                        ? 'signal-value--success'
                                        : 'signal-value--neutral'
                                    }}"
                                >{{ ! empty($session['loaded_assets']) ? 'Yes' : 'No' }}</p>
                            </div>

                            <div class="signal">
                                <h3 class="label">Sensitive paths</h3>
                                
                                <p class="signal-value {{
                                    ! empty($session['requested_sensitive_paths'])
                                        ? 'signal-value--danger'
                                        : 'signal-value--neutral'
                                    }}"
                                >
                                    {{! empty($session['requested_sensitive_paths'])
                                        ? 'Yes'
                                        : 'No'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </header>
            
            {{-- activity, timezone --}}
            <section class="session-entry__activity">
                <div class="item">
                    <h2 class="label">First seen</h2>

                    <time class="datetime" datetime="{{ $firstSeenValue }}">
                        <span class="date">
                            {{ $firstSeenParts['date'] ?? '—' }}
                        </span>

                        <span class="time">
                            {{ $firstSeenParts['time'] ?? '—' }}
                        </span>
                    </time>
                </div>

                <div class="item">
                    <h2 class="label">
                        Last activity
                    </h2>

                    <time class="datetime" datetime="{{ $lastSeenValue }}">
                        <span class="date">
                            {{ $lastSeenParts['date'] ?? '—' }}
                        </span>

                        <span class="time">
                            {{ $lastSeenParts['time'] ?? '—' }}
                        </span>
                    </time>
                </div>

                <div class="item">
                    <h2 class="label">
                        Activity span
                    </h2>

                    <div class="span">
                        <span class="duration">
                            {{ $durationLabel }}
                        </span>

                        <span class="timezone">
                            {{ $firstSeenParts['timezone'] ?? '—' }}
                        </span>
                    </div>
                </div>
            </section>

            <div class="session-entry__tabs" role="tablist" aria-label="Session activity">
                <button class="tab-btn"
                    type="button"
                    role="tab"
                    x-on:click="sessionTab = 'requests'"
                    x-bind:aria-selected="sessionTab === 'requests'"
                    x-bind:class="{'is-active': sessionTab === 'requests'}"
                >
                    Requests

                    <span>({{ $session['requests_count'] ?? 0 }})</span>
                </button>

                <button class="tab-btn"
                    type="button"
                    role="tab"
                    x-on:click="sessionTab = 'events'"
                    x-bind:aria-selected="sessionTab === 'events'"
                    x-bind:class="{'is-active': sessionTab === 'events'}"
                >
                    Events

                    <span>({{ $session['product_events_count'] ?? 0 }})</span>
                </button>
            </div>

            {{-- Contenido de Requests --}}    
            <div class="session-entry__details" x-show="sessionTab === 'requests'">
                {{-- reason --}}
                <section class="session-entry__reason" aria-labelledby="{{ $sessionId }}-reason">
                    <h2 class="section-title" id="{{ $sessionId }}-reason">
                        Reason
                    </h2>
    
                    <p class="reason-text">
                        {{ $session['reason'] ?? '—' }}
                    </p>
                </section>
    
                {{-- paths --}}
                <div class="session-entry__paths">
                    <h2 class="section-title" id="{{ $sessionId }}-paths">Paths</h2>
    
                    <section class="session-entry__paths-section" aria-labelledby="{{ $sessionId }}-paths">
                        <h2 class="requested-paths-title" id="{{ $sessionId }}-paths">
                            requested
                        </h2>
                        
                        @if (empty($visiblePaths))
                            <p class="session-entry__empty-message">
                                No paths recorded.
                            </p>
                        @else
                            <ul class="list">
                                @foreach ($visiblePaths as $path)
                                    <li class="item">
                                        <p class="value">{{ $path }}</p>
                                    </li>
                                @endforeach
    
                                @if ($hiddenPathsCount > 0)
                                    <li class="session-entry__path-item session-entry__path-item--additional" >
                                        {{ $hiddenPathsCount }}
                                        additional paths
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </section>
    
                    <section class="session-entry__paths-section" aria-labelledby="{{ $sessionId }}-sensitive-paths">
                        <h2 class="sensitive-paths-title" id="{{ $sessionId }}-sensitive-paths">
                            Sensitive
                        </h2>
    
                        @if (empty($sensitivePaths))
                            <p class="session-entry__empty-message">
                                No sensitive paths detected.
                            </p>
                        @else
                            <ul class="list">
                                @foreach ($sensitivePaths as $path)
                                    <li class="item">
                                        <p class="value">{{ $path }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>
    
                {{-- navegador --}}
                <section class="session-entry__client" aria-labelledby="{{ $sessionId }}-client">
                    <span class="section-title">user agent</span>
                    <h2 class="user-agent" id="{{ $sessionId }}-client">
                        {{ $session['user_agent'] ?? '—' }}
                    </h2>
                </section>
            </div>

            {{-- Contenido de Events --}}
            <div class="session-entry__details" x-show="sessionTab === 'events'" x-cloak>
                @include('filament.pages.traffic-analysis.session.event-results', [
                    'productSessions' =>  $session['product_sessions'] ?? []
                ])
            </div>
        </article>
    @empty
        <p class="sessions-results__empty" role="status">
            No sessions found for the selected day and filters.
        </p>
    @endforelse
</div>