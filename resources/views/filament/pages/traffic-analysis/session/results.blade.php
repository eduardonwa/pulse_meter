<div class="sessions-results" aria-live="polite">
    @forelse ($this->paginatedSessions as $session)
        @php
            $classification = $session['classification'] ?? 'unknown';
            $risk = $session['risk_level'] ?? 'neutral';
            $paths = $session['paths'] ?? [];
            $visiblePaths = array_slice($paths, 0, 12);
            $hiddenPathsCount =
                max(
                    0,
                    count($paths) - count($visiblePaths)
                );

            $sensitivePaths =
                $session['sensitive_paths'] ?? [];

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
        <article class="session-entry" aria-labelledby="{{ $sessionId }}">
            {{-- general info --}}
            <header>
                <div>
                    {{-- tags, visit overview --}}
                    <dl>
                        <div>
                            <dd>
                                <mark>{{ $classification }}</mark>
                            </dd>
                        </div>

                        <div>
                            <dd>
                                <mark>{{ $risk }}</mark>
                            </dd>
                        </div>

                        <div>
                            <dd>
                                {{ $session['requests_count'] ?? 0 }}
                            </dd>
                        </div>

                        <div>
                            <dd>{{ $durationLabel }}</dd>
                        </div>
                    </dl>

                    {{-- visit type --}}
                    <div aria-labelledby="{{ $sessionId }}-signals">
                        <h2 id="{{ $sessionId }}-signals">
                            Request signals
                        </h2>

                        <dl>
                            <div>
                                <dt>Home</dt>
                                <dd>
                                    {{ ! empty($session['requested_home'])
                                        ? 'Yes'
                                        : 'No' }}
                                </dd>
                            </div>

                            <div>
                                <dt>Assets</dt>
                                <dd>
                                    {{ ! empty($session['loaded_assets'])
                                        ? 'Yes'
                                        : 'No' }}
                                </dd>
                            </div>

                            <div>
                                <dt>Sensitive paths</dt>
                                <dd>
                                    {{ ! empty(
                                        $session[
                                            'requested_sensitive_paths'
                                        ]
                                    )
                                        ? 'Yes'
                                        : 'No' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- IP --}}
                <h2 id="{{ $sessionId }}">
                    <code>{{ $session['ip'] ?? 'Unknown IP' }}</code>
                </h2>
            </header>

            {{-- navegador --}}
            <section aria-labelledby="{{ $sessionId }}-client">
                <dl>
                    <div>
                        <dd>
                            <code>
                                {{ $session['user_agent'] ?? '—' }}
                            </code>
                        </dd>
                    </div>
                </dl>
            </section>
            
            {{-- activity, timezone --}}
            <section>
                <dl>
                    <div>
                        <dt>First seen</dt>
                        <dd>
                            <time datetime="{{ $firstSeenValue }}">
                                <span>
                                    {{ $firstSeenParts['date'] ?? '—' }}
                                </span>

                                <span>
                                    {{ $firstSeenParts['time'] ?? '—' }}
                                </span>
                            </time>
                        </dd>
                    </div>

                    <div>
                        <dt>Last activity</dt>
                        <dd>
                            <time datetime="{{ $lastSeenValue }}">
                                <span>
                                    {{ $lastSeenParts['date'] ?? '—' }}
                                </span>

                                <span>
                                    {{ $lastSeenParts['time'] ?? '—' }}
                                </span>
                            </time>
                        </dd>
                    </div>

                    <div>
                        <dt>Activity span</dt>
                        <dd>{{ $durationLabel }}</dd>
                        <dd> {{ $firstSeenParts['timezone'] ?? '—' }} </dd>
                    </div>
                </dl>
            </section>

            {{-- reason --}}
            <section aria-labelledby="{{ $sessionId }}-reason">
                <h2 id="{{ $sessionId }}-reason">
                    Reason
                </h2>

                <p> {{ $session['reason'] ?? '—' }} </p>
            </section>

            {{-- paths --}}
            <div>
                <section aria-labelledby="{{ $sessionId }}-paths">
                    <h4 id="{{ $sessionId }}-paths">
                        Requested paths
                    </h4>

                    @if (empty($visiblePaths))
                        <p>No paths recorded.</p>
                    @else
                        <ul>
                            @foreach ($visiblePaths as $path)
                                <li>
                                    <code>{{ $path }}</code>
                                </li>
                            @endforeach

                            @if ($hiddenPathsCount > 0)
                                <li>
                                    {{ $hiddenPathsCount }}
                                    additional paths
                                </li>
                            @endif
                        </ul>
                    @endif
                </section>

                <section aria-labelledby="{{ $sessionId }}-sensitive-paths">
                    <h4 id="{{ $sessionId }}-sensitive-paths">
                        Sensitive paths
                    </h4>

                    @if (empty($sensitivePaths))
                        <p>No sensitive paths detected.</p>
                    @else
                        <ul>
                            @foreach ($sensitivePaths as $path)
                                <li>
                                    <code>{{ $path }}</code>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        </article>
    @empty
        <p role="status">
            No sessions found for the selected day and filters.
        </p>
    @endforelse
</div>