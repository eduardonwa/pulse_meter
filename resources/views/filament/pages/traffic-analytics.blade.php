<x-filament-panels::page>
    @php
        $traffic = $this->traffic ?? [];

        $summary = $traffic['summary'] ?? [];

        $generatedAtParts = \App\Services\UserDateFormatter::dateTimeParts(
            $traffic['generated_at'] ?? null,
            auth()->user()
        );

        $cards = [
            [
                'key' => 'human_probable',
                'label' => 'Humanos probables',
                'description' => 'Cargaron home y assets reales.',
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100',
            ],
            [
                'key' => 'scanner',
                'label' => 'Scanners',
                'description' => 'Pidieron rutas sensibles o fueron bloqueados.',
                'class' => 'border-red-200 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100',
            ],
            [
                'key' => 'suspicious',
                'label' => 'Sospechosos',
                'description' => 'Cargaron app y también rutas sensibles.',
                'class' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100',
            ],
            [
                'key' => 'internal',
                'label' => 'Interno',
                'description' => 'Tus IPs o pruebas locales.',
                'class' => 'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100',
            ],
            [
                'key' => 'admin_activity',
                'label' => 'Admin',
                'description' => 'Actividad en Filament o panel interno.',
                'class' => 'border-violet-200 bg-violet-50 text-violet-900 dark:border-violet-800 dark:bg-violet-950 dark:text-violet-100',
            ],
            [
                'key' => 'unknown',
                'label' => 'Unknown',
                'description' => 'No hay suficiente evidencia.',
                'class' => 'border-gray-200 bg-gray-50 text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100',
            ],
        ];

        $badgeClasses = [
            'human_probable' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
            'scanner' => 'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300',
            'suspicious' => 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
            'internal' => 'bg-sky-100 text-sky-800 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
            'admin_activity' => 'bg-violet-100 text-violet-800 ring-violet-600/20 dark:bg-violet-500/10 dark:text-violet-300',
            'unknown' => 'bg-gray-100 text-gray-800 ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-300',
        ];

        $riskClasses = [
            'clean' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
            'blocked' => 'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300',
            'suspicious' => 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
            'ignored' => 'bg-sky-100 text-sky-800 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
            'neutral' => 'bg-gray-100 text-gray-800 ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-300',
        ];
    @endphp

    @if (empty($traffic))
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-lg font-semibold text-gray-950 dark:text-white">
                No traffic summary found yet.
            </div>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Corre el comando para generar el resumen:
            </p>

            <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-sm text-gray-100">php artisan traffic:generate-summary</pre>
        </div>
    @else
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                            Traffic Analytics
                        </h2>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Sesiones agrupadas por IP + User-Agent + ventana de tiempo.
                        </p>
                    </div>

                    <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-gray-950">
                        <div class="text-gray-500 dark:text-gray-400">Last update</div>

                        <div class="mt-1 flex flex-col gap-0.5">
                            <div class="font-medium text-gray-950 dark:text-white">
                                {{ $generatedAtParts['date'] ?? '—' }}
                            </div>

                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $generatedAtParts['time'] ?? '—' }}
                            </div>

                            <div class="text-xs text-gray-500 dark:text-gray-500">
                                {{ $generatedAtParts['timezone'] ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Requests</div>
                        <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                            {{ $traffic['total_requests'] ?? 0 }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Sessions</div>
                        <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                            {{ $traffic['total_sessions'] ?? 0 }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Skipped lines</div>
                        <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                            {{ $traffic['skipped_lines'] ?? 0 }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Session gap</div>
                        <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                            {{ $traffic['session_gap_minutes'] ?? 30 }} min
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($cards as $card)
                    <div class="rounded-2xl border p-5 shadow-sm {{ $card['class'] }}">
                        <div class="text-sm font-medium opacity-75">
                            {{ $card['label'] }}
                        </div>

                        <div class="mt-3 text-4xl font-bold">
                            {{ $summary[$card['key']] ?? 0 }}
                        </div>

                        <div class="mt-3 text-xs leading-5 opacity-75">
                            {{ $card['description'] }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-5 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                        Sessions
                    </h3>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Aquí ya no estás viendo líneas sueltas del log, sino visitas interpretadas.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 p-5 dark:border-gray-800">
                    @foreach ($cards as $card)
                        @php
                            $key = $card['key'];
                            $isActive = $this->sessionTypeFilters[$key] ?? false;
                        @endphp

                        <button
                            type="button"
                            wire:click="toggleSessionTypeFilter('{{ $key }}')"
                            class="rounded-full px-3 py-1.5 text-xs font-medium ring-1 ring-inset transition
                                {{ $isActive
                                    ? 'bg-primary-600 text-white ring-primary-600'
                                    : 'bg-gray-100 text-gray-500 ring-gray-300 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700'
                                }}"
                        >
                            {{ $card['label'] }}
                        </button>
                    @endforeach

                    <button
                        type="button"
                        wire:click="resetSessionTypeFilters"
                        class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="previousSessionsPage"
                        @disabled($this->sessionsPage <= 1)
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200"
                    >
                        Anterior
                    </button>

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Página {{ $this->sessionsPage }} de {{ $this->getTotalSessionPages() }}
                    </div>

                    <button
                        type="button"
                        wire:click="nextSessionsPage"
                        @disabled($this->sessionsPage >= $this->getTotalSessionPages())
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200"
                    >
                        Siguiente
                    </button>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($this->paginatedSessions as $session)
                        @php
                            $classification = $session['classification'] ?? 'unknown';
                            $risk = $session['risk_level'] ?? 'neutral';
                            $paths = $session['paths'] ?? [];
                            $visiblePaths = array_slice($paths, 0, 12);
                            $hiddenPathsCount = max(0, count($paths) - count($visiblePaths));
                            $sensitivePaths = $session['sensitive_paths'] ?? [];

                            $firstSeenParts = \App\Services\UserDateFormatter::dateTimeParts(
                                $session['first_seen'] ?? $session['first_seen_timestamp'] ?? null,
                                auth()->user()
                            );

                            $lastSeenParts = \App\Services\UserDateFormatter::dateTimeParts(
                                $session['last_seen'] ?? $session['last_seen_timestamp'] ?? null,
                                auth()->user()
                            );

                            $durationLabel = \App\Services\UserDateFormatter::duration(
                                $session['duration_seconds'] ?? 0
                            );
                        @endphp

                        <div class="p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeClasses[$classification] ?? $badgeClasses['unknown'] }}">
                                            {{ $classification }}
                                        </span>

                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $riskClasses[$risk] ?? $riskClasses['neutral'] }}">
                                            {{ $risk }}
                                        </span>

                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $session['requests_count'] ?? 0 }} requests
                                        </span>

                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $durationLabel }}
                                        </span>
                                    </div>

                                    <div class="mt-3 font-mono text-sm text-gray-950 dark:text-white">
                                        {{ $session['ip'] ?? '—' }}
                                    </div>

                                    <div class="mt-2 max-w-4xl truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $session['user_agent'] ?? '—' }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-3 text-center text-xs">
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-gray-950">
                                        <div class="text-gray-500 dark:text-gray-400">Home</div>
                                        <div class="mt-1 font-semibold {{ ! empty($session['requested_home']) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                            {{ ! empty($session['requested_home']) ? 'yes' : 'no' }}
                                        </div>
                                    </div>

                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-gray-950">
                                        <div class="text-gray-500 dark:text-gray-400">Assets</div>
                                        <div class="mt-1 font-semibold {{ ! empty($session['loaded_assets']) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                            {{ ! empty($session['loaded_assets']) ? 'yes' : 'no' }}
                                        </div>
                                    </div>

                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-gray-950">
                                        <div class="text-gray-500 dark:text-gray-400">Sensitive</div>
                                        <div class="mt-1 font-semibold {{ ! empty($session['requested_sensitive_paths']) ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}">
                                            {{ ! empty($session['requested_sensitive_paths']) ? 'yes' : 'no' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-950">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Reason
                                </div>

                                <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                    {{ $session['reason'] ?? '—' }}
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <div>
                                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Paths
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @forelse ($visiblePaths as $path)
                                            <span class="rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $path }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Sin paths.</span>
                                        @endforelse

                                        @if ($hiddenPathsCount > 0)
                                            <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                +{{ $hiddenPathsCount }} más
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Sensitive paths
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @forelse ($sensitivePaths as $path)
                                            <span class="rounded-lg bg-red-50 px-2.5 py-1 font-mono text-xs text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                                {{ $path }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Ninguno.</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 text-xs text-gray-500 dark:text-gray-400 md:grid-cols-3">
                                <div>
                                    <div class="font-medium text-gray-700 dark:text-gray-300">Inicio</div>
                                    <div>{{ $firstSeenParts['date'] }}</div>
                                    <div>{{ $firstSeenParts['time'] }}</div>
                                </div>

                                <div>
                                    <div class="font-medium text-gray-700 dark:text-gray-300">Última actividad</div>
                                    <div>{{ $lastSeenParts['date'] }}</div>
                                    <div>{{ $lastSeenParts['time'] }}</div>
                                </div>

                                <div>
                                    <div class="font-medium text-gray-700 dark:text-gray-300">Duración observada</div>
                                    <div>{{ $durationLabel }}</div>
                                    <div>{{ $firstSeenParts['timezone'] }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-sm text-gray-500 dark:text-gray-400">
                            No hay sesiones todavía.
                        </div>
                    @endforelse
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 p-5 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="previousSessionsPage"
                        @disabled($this->sessionsPage <= 1)
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200"
                    >
                        Anterior
                    </button>

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Página {{ $this->sessionsPage }} de {{ $this->getTotalSessionPages() }}
                    </div>

                    <button
                        type="button"
                        wire:click="nextSessionsPage"
                        @disabled($this->sessionsPage >= $this->getTotalSessionPages())
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        </div>
    @endif

    @script
        <script>
            $wire.on('traffic-sessions-page-changed', () => {
                setTimeout(() => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth',
                    });
                }, 100);
            });
        </script>
    @endscript
</x-filament-panels::page>