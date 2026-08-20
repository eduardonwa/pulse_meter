@props([
    'user',
])

@php
    $trial = $user?->trialEntitlement;
@endphp

@if (
    $user
    && ! $user->hasPaidProAccess()
    && $trial
    && in_array(
        $trial->status,
        ['active', 'paused'],
        true
    )
)
    <div
        class="trial-mode-nav"
        data-trial-mode-status="{{ $trial->status }}"

        @if (
            $trial->status === 'active'
            && $user->can('use-pro')
        )
            data-heartbeat-url="{{ route('trial.heartbeat') }}"
            data-remaining-seconds="{{ $trial->remainingSeconds() }}"
        @endif
    >
        <span class="trial-mode-nav__label">
            Trial
        </span>

        @if (
            $trial->status === 'active'
            && $user->can('use-pro')
        )
            <span
                class="trial-mode-nav__state"
                data-trial-state
            >
                Running
            </span>

            <strong
                class="trial-mode-nav__time"
                data-trial-time
            >
                {{ $trial->remainingTimeLabel() }}
            </strong>

            <form
                method="POST"
                action="{{ route('trial.pause') }}"
                data-trial-pause-form
            >
                @csrf

                <button
                    class="button"
                    data-type="icon"
                    type="submit"
                    aria-label="Pause Trial Mode"
                    title="Pause Trial Mode"
                >
                    <x-heroicon-s-pause
                        width="18"
                        height="18"
                        aria-hidden="true"
                    />
                </button>
            </form>
        @elseif ($trial->status === 'paused')
            <span class="trial-mode-nav__state">
                Paused
            </span>

            <strong class="trial-mode-nav__time">
                {{ $trial->remainingTimeLabel() }}
            </strong>

            <form
                method="POST"
                action="{{ route('trial.resume') }}"
                data-trial-resume-form
            >
                @csrf

                <button
                    class="button"
                    data-type="icon"
                    type="submit"
                    aria-label="Resume Trial Mode"
                    title="Resume Trial Mode"
                >
                    <x-heroicon-s-play
                        width="18"
                        height="18"
                        aria-hidden="true"
                    />
                </button>
            </form>
        @endif
    </div>
@endif

@if (
    session('trial_status')
    || session('trial_error')
)
    <div class="trial-mode-nav__message">
        @if (session('trial_status'))
            <span role="status">
                {{ session('trial_status') }}
            </span>
        @endif

        @if (session('trial_error'))
            <span role="alert">
                {{ session('trial_error') }}
            </span>
        @endif
    </div>
@endif