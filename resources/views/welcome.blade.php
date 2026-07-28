<x-layouts.dorelog>
    <div class="account-bar">
        @guest
            <section>
                <p>Create an account to access Trial Mode.</p>

                <a href="{{ route('register') }}">
                    Register
                </a>
            </section>
        @endguest

        @auth
            @php
                $user = auth()->user();
                $trial = $user->trialEntitlement;
            @endphp

            {{-- Usuario con plan Pro pagado --}}
            @if ($user->plan === 'pro')
                <span>Pro access enabled.</span>

            {{-- Usuario usando su trial --}}
            @elseif ($trial?->status === 'active' && $user->can('use-pro'))
                <div
                    id="trial-mode-status"
                    data-heartbeat-url="{{ route('trial.heartbeat') }}"
                    data-remaining-seconds="{{ $trial->remainingSeconds() }}"
                >
                    Trial Mode active ·

                    <span id="trial-time-remaining">
                        {{ $trial->remainingTimeLabel() }}
                    </span>

                    remaining
                </div>

            {{-- Usuario que nunca ha activado el trial --}}
            @elseif (! $trial)
                <form method="POST" action="{{ route('trial.activate') }}">
                    @csrf

                    <button type="submit">
                        Enable Trial Mode
                    </button>
                </form>

            {{-- Trial consumido o vencido --}}
            @elseif (in_array($trial->status, ['completed', 'expired'], true))
                <span>Trial complete · Continue creating with Pro.</span>

            {{-- Trial pausado --}}
            @elseif ($trial->status === 'paused')
                <div>
                    <span>
                        Trial Mode paused ·
                        {{ $trial->remainingTimeLabel() }} remaining
                    </span>

                    <form method="POST" action="{{ route('trial.resume') }}">
                        @csrf

                        <button type="submit">
                            Resume Trial Mode
                        </button>
                    </form>
                </div>
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
        x-data="routinePlayer()"
        @keydown.window="handleKeydown($event)"
    >
        <x-metronome.main-metro />

        <x-metronome.panel />

        <x-windows.confirm-modal />
    </main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const trialStatus = document.getElementById('trial-mode-status');
        const timeElement = document.getElementById('trial-time-remaining');

        if (!trialStatus || !timeElement) {
            return;
        }

        const heartbeatUrl = trialStatus.dataset.heartbeatUrl;

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        let remainingSeconds = Number(
            trialStatus.dataset.remainingSeconds
        );

        let heartbeatIntervalId = null;
        let visualIntervalId = null;
        let requestInProgress = false;

        /*
         * Cuando regresamos a la pestaña, detenemos el contador
         * visual mientras Laravel calcula el saldo verdadero.
         */
        let waitingForVisibilitySync = false;

        function formatTime(totalSeconds) {
            const safeSeconds = Math.max(0, totalSeconds);
            const minutes = Math.floor(safeSeconds / 60);
            const seconds = safeSeconds % 60;

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function renderTime() {
            timeElement.textContent = formatTime(remainingSeconds);
        }

        async function sendTrialHeartbeat(event = 'heartbeat') {
            const isHiddenSignal = event === 'hidden';

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
                const response = await fetch(heartbeatUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        event: event,
                    }),
                    keepalive: isHiddenSignal,
                });

                if (!response.ok) {
                    throw new Error(
                        `Heartbeat request failed: ${response.status}`
                    );
                }

                const data = await response.json();

                /*
                 * Toda referencia a "data" permanece dentro
                 * de este bloque.
                 */
                if (Number.isInteger(data.remaining_seconds)) {
                    remainingSeconds = data.remaining_seconds;
                    renderTime();
                }

                /*
                 * La señal "hidden" solamente registra la salida.
                 * No recargamos una pestaña que está oculta.
                 */
                if (!isHiddenSignal) {
                    waitingForVisibilitySync = false;

                    if (data.status !== 'active') {
                        window.clearInterval(heartbeatIntervalId);
                        window.clearInterval(visualIntervalId);
                        window.location.reload();
                    }
                }
            } catch (error) {
                console.error('Trial heartbeat failed.', error);
            } finally {
                if (!isHiddenSignal) {
                    requestInProgress = false;
                }
            }
        }

        renderTime();

        /*
         * Contador visual de un segundo.
         */
        visualIntervalId = window.setInterval(() => {
            if (
                document.hidden ||
                waitingForVisibilitySync ||
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
         * Heartbeat inicial.
         */
        sendTrialHeartbeat();

        /*
         * Heartbeat normal cada 10 segundos.
         */
        heartbeatIntervalId = window.setInterval(
            sendTrialHeartbeat,
            10_000
        );

        /*
         * Registrar la salida y sincronizar al regresar.
         */
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                waitingForVisibilitySync = true;
                sendTrialHeartbeat('hidden');

                return;
            }

            sendTrialHeartbeat('heartbeat');
        });
    });
</script>
</x-layouts.dorelog>