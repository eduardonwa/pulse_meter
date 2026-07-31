@php
    $user = auth()->user();
@endphp

<x-layouts.dorelog :user="$user">
    <div class="account-bar">
        @guest
            <section class="trial-mode-banner">
                <p> Create an account to access <span class="fw-bold">Trial Mode</span>. </p>

                <a class="badge" href="{{ route('register') }}">
                    Register
                </a>
            </section>
        @endguest

        @auth
            @php
                $trial = $user->trialEntitlement;
            @endphp

            @if ($user->plan !== 'pro')
                {{-- Trial activo y disponible para esta cuenta --}}
                @if ($trial?->status === 'active' && $user->can('use-pro'))
                    <div class="trial-mode-status">
                        <div
                            id="trial-mode-status"
                            data-heartbeat-url="{{ route('trial.heartbeat') }}"
                            data-remaining-seconds="{{ $trial->remainingSeconds() }}"
                        >
                            <span>Trial Mode</span>

                            <span id="trial-running-status">
                                Running
                            </span>

                            <span>
                                ·
                                <span id="trial-time-remaining">
                                    {{ $trial->remainingTimeLabel() }}
                                </span>
                                remaining
                            </span>
                        </div>

                        <form method="POST" action="{{ route('trial.pause') }}">
                            @csrf

                            <button type="submit">
                                Pause Trial Mode
                            </button>
                        </form>
                    </div>
                {{-- Nunca ha activado el trial --}}
                @elseif (! $trial)
                    <form method="POST" action="{{ route('trial.activate') }}">
                        @csrf

                        <button type="submit">
                            Enable Trial Mode
                        </button>
                    </form>

                {{-- Trial consumido o vencido --}}
                @elseif (in_array($trial->status, ['completed', 'expired'], true))
                    <span>
                        Trial complete · Continue creating with Pro.
                    </span>

                {{-- Trial pausado --}}
                @elseif ($trial->status === 'paused')
                    <div>
                        <span>
                            Trial Mode paused ·
                            {{ $trial->remainingTimeLabel() }}
                            remaining
                        </span>

                        <form method="POST" action="{{ route('trial.resume') }}">
                            @csrf

                            <button type="submit">
                                Resume Trial Mode
                            </button>
                        </form>
                    </div>
                @endif
            @endif
        @endauth

        <div class="trial-status">
            @if (session('trial_status'))
                <p role="status">
                    {{ session('trial_status') }}
                </p>
            @endif

            @if (session('trial_error'))
                <p role="alert">
                    {{ session('trial_error') }}
                </p>
            @endif
        </div>
    </div>

    <main
        class="metronome | container"
        data-type="wide"
        data-spacing="none"
        x-data="routinePlayer(
            @js($routine->steps),
            @js($pulsePresets)
        )"
        @keydown.window="handleKeydown($event)"
        @click.window="handleToolTetherClick($event)"
        @pointermove.window="handleToolTetherPointerMove($event)"

        data-pulse-presets-store-url="{{ route('pulse-presets.store') }}"
        data-pulse-presets-index-url="{{ route('pulse-presets.index') }}"
        data-pulse-presets-update-url="{{ route('pulse-presets.update', ['pulsePreset' => '__ID__']) }}"
        data-pulse-presets-destroy-url="{{ route('pulse-presets.destroy', ['pulsePreset' => '__ID__']) }}"
    >
        <x-tether />
        
        <x-metronome.main-metro />

        <x-metronome.panel />

        <x-windows.confirm-modal />

        <x-windows.pattern-modal />

        <x-windows.pattern-delete />
        
        <x-windows.pattern-rename />

        <x-toaster />
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            /*
            * Reutilizamos tu session_id existente.
            */
            const trialSessionId =
                globalThis.dorelogClientIds?.getSessionId?.();

            if (!trialSessionId) {
                console.error(
                    'DoreLog session_id could not be loaded.'
                );

                return;
            }

            /*
            * Los formularios Pause y Resume también necesitan
            * decirle a Laravel desde qué pestaña vienen.
            */
            const pauseForm = document.querySelector(
                'form[action$="/trial/pause"]'
            );

            const resumeForm = document.querySelector(
                'form[action$="/trial/resume"]'
            );

            function attachSessionId(form) {
                if (!form) {
                    return;
                }

                form.addEventListener('submit', () => {
                    let input = form.querySelector(
                        'input[name="session_id"]'
                    );

                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'session_id';

                        form.appendChild(input);
                    }

                    input.value = trialSessionId;
                });
            }

            attachSessionId(pauseForm);
            attachSessionId(resumeForm);

            /*
            * En una pantalla pausada no existe el contador activo.
            * Ya configuramos Resume arriba, así que podemos salir.
            */
            const trialStatus =
                document.getElementById('trial-mode-status');

            const timeElement =
                document.getElementById('trial-time-remaining');

            if (!trialStatus || !timeElement) {
                return;
            }

            const heartbeatUrl =
                trialStatus.dataset.heartbeatUrl;

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            let remainingSeconds = Number(
                trialStatus.dataset.remainingSeconds
            );

            let heartbeatIntervalId = null;
            let visualIntervalId = null;

            let requestInProgress = false;
            let waitingForVisibilitySync = false;
            let blockedByOtherSession = false;

            function formatTime(totalSeconds) {
                const safeSeconds = Math.max(
                    0,
                    totalSeconds
                );

                const minutes = Math.floor(
                    safeSeconds / 60
                );

                const seconds =
                    safeSeconds % 60;

                return `${
                    String(minutes).padStart(2, '0')
                }:${
                    String(seconds).padStart(2, '0')
                }`;
            }

            function renderTime() {
                timeElement.textContent =
                    formatTime(remainingSeconds);
            }

            async function sendTrialHeartbeat(
                event = 'heartbeat'
            ) {
                const isHiddenSignal =
                    event === 'hidden';

                if (
                    (!isHiddenSignal && document.hidden) ||
                    (!isHiddenSignal && requestInProgress) ||
                    !csrfToken
                ) {
                    return;
                }

                if (!isHiddenSignal) {
                    requestInProgress = true;
                }

                try {
                    const response = await fetch(
                        heartbeatUrl,
                        {
                            method: 'POST',
                            credentials: 'same-origin',

                            headers: {
                                Accept: 'application/json',
                                'Content-Type':
                                    'application/json',
                                'X-CSRF-TOKEN':
                                    csrfToken,
                            },

                            body: JSON.stringify({
                                event: event,
                                session_id:
                                    trialSessionId,
                            }),

                            keepalive:
                                isHiddenSignal,
                        }
                    );

                    const data =
                        await response.json();

                    /*
                    * Otra pestaña posee actualmente
                    * el Trial Mode.
                    */
                    if (
                        response.status === 409 &&
                        data.status === 'active_elsewhere'
                    ) {
                        blockedByOtherSession = true;
                        waitingForVisibilitySync = true;

                        trialStatus.textContent =
                            data.message ??
                            'Trial Mode is active in another tab.';

                        pauseForm?.remove();

                        return;
                    }

                    if (!response.ok) {
                        throw new Error(
                            `Heartbeat request failed: ${
                                response.status
                            }`
                        );
                    }

                    blockedByOtherSession = false;

                    if (
                        Number.isInteger(
                            data.remaining_seconds
                        )
                    ) {
                        remainingSeconds =
                            data.remaining_seconds;

                        renderTime();
                    }

                    if (!isHiddenSignal) {
                        waitingForVisibilitySync = false;

                        /*
                        * Si la otra pestaña quedó pausada,
                        * completó el trial, etc.,
                        * recargamos para mostrar el estado real.
                        */
                        if (data.status !== 'active') {
                            window.clearInterval(
                                heartbeatIntervalId
                            );

                            window.clearInterval(
                                visualIntervalId
                            );

                            window.location.reload();

                            return;
                        }
                    }
                } catch (error) {
                    console.error(
                        'Trial heartbeat failed.',
                        error
                    );
                } finally {
                    if (!isHiddenSignal) {
                        requestInProgress = false;
                    }
                }
            }

            renderTime();

            /*
            * Contador visual.
            */
            visualIntervalId =
                window.setInterval(() => {
                    if (
                        document.hidden ||
                        waitingForVisibilitySync ||
                        blockedByOtherSession ||
                        remainingSeconds <= 0
                    ) {
                        return;
                    }

                    remainingSeconds -= 1;
                    renderTime();

                    if (remainingSeconds === 0) {
                        sendTrialHeartbeat();
                    }
                }, 1_000);

            /*
            * Primer heartbeat:
            * aquí esta pestaña intenta reclamar
            * active_session_id.
            */
            sendTrialHeartbeat();

            /*
            * Sincronización normal.
            *
            * Incluso una pestaña bloqueada sigue preguntando
            * cada 10 segundos. No consume tiempo; esto permite
            * detectar cuando la pestaña original se auto-pausa.
            */
            heartbeatIntervalId =
                window.setInterval(
                    sendTrialHeartbeat,
                    10_000
                );

            document.addEventListener(
                'visibilitychange',
                () => {
                    if (document.hidden) {
                        waitingForVisibilitySync = true;

                        /*
                        * Una pestaña bloqueada no es dueña
                        * del trial, así que no registra salida.
                        */
                        if (!blockedByOtherSession) {
                            sendTrialHeartbeat('hidden');
                        }

                        return;
                    }

                    /*
                    * Al regresar comprobamos inmediatamente
                    * quién posee el trial.
                    */
                    sendTrialHeartbeat();
                }
            );
        });
    </script>
</x-layouts.dorelog>