@php
    $template = $routine->routineTemplate;
    $steps = $template->steps;

    $totalSeconds = $steps->sum(
        fn ($step) =>
            $step->mode === 'timer'
                ? ($step->duration_seconds ?? 0)
                : 0
    );

    $totalMinutes = (int) ceil(
        $totalSeconds / 60
    );

    $coverUrl = $template->cover_image
        ? Storage::disk('public')->url(
            $template->cover_image
        )
        : null;

    $initialStep = $steps->first();

    $initialStepName = $initialStep
        ? (
            app()->isLocale('en')
                ? (
                    $initialStep->name_en
                    ?: $initialStep->name_es
                )
                : $initialStep->name_es
        )
        : 'Exercise';

    $initialBpm = $initialStep?->bpm ?? 100;
@endphp

<x-layouts.dorelog>
    <main class="routine-player"

        @if (in_array($viewerType, ['trial', 'pro', 'lifetime'], true))
            x-data="routinePlayer(
                @js($practiceContext),
                @js($pulsePresets)
            )"

            @keydown.window="handleKeydown($event)"
        @else
            x-data="routineTemplateGuest({
                steps: @js($practiceContext['queue'])
            })"
        @endif

        data-template-title="{{ $routine->title }}"
        
        @if (in_array($viewerType, ['guest', 'free'], true))
            data-use-template-url="{{ route('welcome') }}"
        @endif

        @if (in_array($viewerType, ['pro', 'lifetime'], true))
            data-save-template-copy-url="{{ route('routines.save-copy', [
                'routineTemplate' => $template->id,
            ]) }}"
        @endif
    >
        <div class="routine-player__container container">
            <nav class="routine-detail__navigation" aria-label="Routines">
                <a class="button" data-type="icon-text"
                    href="{{ route('routines.index', [
                        'locale' => app()->getLocale(),
                    ]) }}"
                >
                    <x-heroicon-o-arrow-left
                        class="routine-detail__back-icon"
                        width="18"
                        height="18"
                        aria-hidden="true"
                    />

                    <span>Routines</span>
                </a>
            </nav>

            <header class="routine-player__header">
                <h1 class="heading-3">
                    {{ $routine->title }}
                </h1>
            </header>

            <div class="routine-player__layout">
                <aside class="routine-player__sidebar">
                    @include('routines.partials.template-player')
                    @include('routines.partials.template-controls')
                    @include('routines.partials.template-details')
                </aside>

                @include('routines.partials.template-exercises')
            </div>
        </div>
        
        @if (in_array( $viewerType, ['trial', 'pro', 'lifetime'], true))
            <x-windows.advance-modal />
        @endif

        <x-windows.confirm-modal />
    </main>

    <x-windows.pro-upsell-modal :viewer-type="$viewerType" />
</x-layouts.dorelog>