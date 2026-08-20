@php
    $user = auth()->user();
@endphp

<x-layouts.dorelog :user="$user">
    <div class="account-bar">
        @guest
            @if (Route::has('register'))
                <section class="trial-mode-banner">
                    <p>
                        Create an account to access
                        <span class="fw-bold">
                            Trial Mode
                        </span>
                    </p>

                    <a class="badge badge--trial-register" href="{{ route('register') }}">
                        Register
                    </a>
                </section>
            @endif
        @endguest

        @auth
            @php
                $trial = $user->trialEntitlement;
            @endphp

            @if (! $user->hasPaidProAccess())
                @if (! $trial)
                    <form
                        class="form-panel enable-trial"
                        method="POST"
                        action="{{ route('trial.activate') }}"
                    >
                        @csrf

                        <button
                            class="badge badge--trial-register"
                            type="submit"
                        >
                            Enable Trial Mode
                        </button>
                    </form>

                @elseif (
                    in_array(
                        $trial->status,
                        ['completed', 'expired'],
                        true
                    )
                )
                    <div
                        class="
                            trial-mode-status
                            trial-mode-status--completed
                        "
                    >
                        <span>
                            Trial Mode completed
                        </span>

                        <a
                            class="badge badge--trial-register"
                            href="#"
                        >
                            Continue creating with Pro
                        </a>
                    </div>
                @endif
            @endif
        @endauth
    </div>

    <main class="metronome | container" data-type="wide" data-spacing="none"
        x-data="routinePlayer(
            @js($practiceContext),
            @js($pulsePresets)
        )"
        
        @if ($usesServerPersistence && $user)
            data-local-routine-import-url="{{ route('practice-routines.import-local') }}"
            data-local-routine-import-marker-key="pulse_meter_routine_import_user_{{ $user->id }}"
        @endif
        
        @keydown.window="handleKeydown($event)"
        @click.window="handleToolTetherClick($event)"
        @pointermove.window="handleToolTetherPointerMove($event)"

        @if ($usesServerPersistence && $routine)
            {{-- ROUTINE STEPS --}}
            data-routine-steps-store-url="{{ route(
                'practice-routine-steps.store',
                ['practiceRoutine' => $routine['id']]
            ) }}"

            data-routine-step-update-url="{{ route(
                'practice-routine-steps.update',
                ['practiceRoutineStep' => '__ID__']
            ) }}"

            data-routine-step-destroy-url="{{ route(
                'practice-routine-steps.destroy',
                ['practiceRoutineStep' => '__ID__']
            ) }}"
        @endif

        @if ($usesServerPersistence)
            {{-- PULSE PRESETS --}}
            data-pulse-presets-store-url="{{ route(
                'pulse-presets.store'
            ) }}"

            data-pulse-presets-index-url="{{ route(
                'pulse-presets.index'
            ) }}"

            data-pulse-presets-update-url="{{ route(
                'pulse-presets.update',
                ['pulsePreset' => '__ID__']
            ) }}"

            data-pulse-presets-destroy-url="{{ route(
                'pulse-presets.destroy',
                ['pulsePreset' => '__ID__']
            ) }}"
        @endif

        x-on:show-toast.window="
            showToast(
                $event.detail.message,
                $event.detail.type
            )
        "
        @routine-renamed.window="
            Number(activeRoutine?.id) === Number($event.detail.id)
                && (activeRoutine.name = $event.detail.name)
        "
        @routine-exercises-updated.window="
            Number(activeRoutine?.id) === Number($event.detail.routineId)
                && (steps = $event.detail.exercises)
        "
        @open-confirm-modal.window="
            confirmModal.title = $event.detail.title
            confirmModal.message = $event.detail.message
            confirmModal.confirmLabel = $event.detail.confirmLabel
            confirmModal.isOpen = true

            confirmModal.action = async () => {
                await Livewire
                    .find($event.detail.componentId)
                    .call(
                        $event.detail.method,
                        ...$event.detail.arguments
                    )
            }
        "
    >
        <x-tether />
        
        <x-metronome.main-metro />

        <x-metronome.panel
            :routine="$routine"
            :routines="$routines"
            :uses-server-persistence="$usesServerPersistence"
        />

        <x-windows.confirm-modal />

        <x-windows.pattern-modal />

        <x-windows.pattern-delete />
        
        @if ($usesServerPersistence)
            <x-windows.local-routine-import-modal />
        @endif

        <livewire:practice-dialog
            :routine="$routine"
            :routines="$routines->all()"
            :uses-server-persistence="$usesServerPersistence"
        />

        <x-toaster />
    </main>
</x-layouts.dorelog>