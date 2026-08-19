@php
    $template = $routine->routineTemplate;
    $steps = $template->steps;
    
    $totalSeconds = $steps->sum(
        fn ($step) =>
            $step->mode === 'timer'
                ? ($step->duration_seconds ?? 0)
                : 0
    );

    $totalMinutes = (int) ceil($totalSeconds / 60);

    $typeLabel = str($template->type)
        ->replace('_', ' ')
        ->title();

    $coverUrl = $template->cover_image
        ? Storage::disk('public')->url(
            $template->cover_image
        )
        : null;

    $initialStep = $template->steps->first();

    $initialStepName = $initialStep
        ? (
            app()->isLocale('en')
                ? ($initialStep->name_en ?: $initialStep->name_es)
                : $initialStep->name_es
        )
        : 'Exercise';

    $initialBpm = $initialStep?->bpm ?? 100;
@endphp

<x-layouts.dorelog>
    <main class="routine-player"
        x-data="{
            activeStepId: @js($initialStep?->id),
            currentName: @js($initialStepName),
            bpm: {{ $initialBpm }},
        }"
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
                    <section class="routine-player__metronome"
                        @if ($coverUrl)
                            style="--routine-cover-image: url('{{ $coverUrl }}');"
                        @endif
                    >
                        <div class="routine-player__metronome-content">
                            <header class="routine-player__current">
                                <span class="routine-player__eyebrow">
                                    Current exercise
                                </span>

                                <h2 class="routine-player__current-name" x-text="currentName">
                                    {{ $initialStepName }}
                                </h2>
                            </header>

                            <div class="routine-player__beats" aria-label="Metronome beats">
                                @foreach (range(1, 4) as $beat)
                                    <span
                                        @class([
                                            'routine-player__beat',
                                            'routine-player__beat--active' => $beat === 1,
                                        ])
                                    >
                                        {{ $beat }}
                                    </span>
                                @endforeach
                            </div>

                            <div class="routine-player__tempo">
                                <strong class="routine-player__tempo-value" x-text="bpm">
                                    {{ $initialBpm }}
                                </strong>

                                <span class="routine-player__tempo-unit">
                                    BPM
                                </span>
                            </div>

                            <input
                                class="routine-player__tempo-control"
                                type="range"
                                min="30"
                                max="400"
                                x-model.number="bpm"
                                value="{{ $initialBpm }}"
                                aria-label="Tempo"
                            />

                            <button
                                class="routine-player__start button"
                                data-type="primary"
                                type="button"
                            >
                                Start
                            </button>
                        </div>
                    </section>

                    <section class="routine-player__details">
                        <h2 class="routine-player__section-title">
                            About this routine
                        </h2>

                        <dl class="routine-player__facts">
                            <div class="routine-player__fact">
                                <dt>Instrument</dt>

                                <dd>
                                    {{ str($template->instrument)->title() }}
                                </dd>
                            </div>

                            <div class="routine-player__fact">
                                <dt>Difficulty</dt>

                                <dd>
                                    {{ str($template->difficulty)->title() }}
                                </dd>
                            </div>
                            
                            @if ($totalSeconds > 0)
                                <div class="routine-player__fact">
                                    <dt>Duration</dt>

                                    <dd>{{ $totalMinutes }} minutes</dd>
                                </div>
                            @endif

                            <div class="routine-player__fact">
                                <dt>Exercises</dt>

                                <dd>{{ $template->steps->count() }}</dd>
                            </div>
                        </dl>

                        <div class="routine-player__description">
                            <h3>Summary</h3>

                            <p>{{ $routine->summary }}</p>
                        </div>

                        @if ($routine->purpose)
                            <div class="routine-player__description">
                                <h3>Purpose</h3>

                                <p>{{ $routine->purpose }}</p>
                            </div>
                        @endif

                        @if ($routine->instructions)
                            <div class="routine-player__description">
                                <h3>How to use it</h3>

                                <p>{{ $routine->instructions }}</p>
                            </div>
                        @endif
                    </section>
                </aside>

                <section class="routine-player__queue">
                    <header class="routine-player__queue-header">
                        <h2 class="routine-player__section-title">
                            Exercises
                        </h2>

                        <span>
                            {{ $template->steps->count() }}
                        </span>
                    </header>

                    <ol class="routine-player__exercise-list">
                        @foreach ($template->steps as $step)
                            @php
                                $stepName = app()->isLocale('en')
                                    ? ($step->name_en ?: $step->name_es)
                                    : $step->name_es;

                                $stepNotes = app()->isLocale('en')
                                    ? ($step->notes_en ?: $step->notes_es)
                                    : $step->notes_es;

                                $duration = $step->duration_seconds;

                                $durationLabel = match (true) {
                                    ! $duration => null,
                                    $duration < 60 => "{$duration} sec",
                                    $duration % 60 === 0 =>
                                        ((int) ($duration / 60)) . ' min',
                                    default =>
                                        intdiv($duration, 60)
                                        . ' min '
                                        . ($duration % 60)
                                        . ' sec',
                                };
                            @endphp

                            <li class="routine-player__exercise">
                                <button class="routine-player__exercise-trigger" type="button"
                                    :class="{
                                        'routine-player__exercise-trigger--active':
                                            activeStepId === {{ $step->id }}
                                    }"
                                    :aria-pressed="
                                        (activeStepId === {{ $step->id }}).toString()
                                    "
                                    @click="
                                        activeStepId = {{ $step->id }};
                                        currentName = @js($stepName);
                                        bpm = {{ $step->bpm }};
                                    "
                                >
                                    <span class="routine-player__exercise-position">
                                        {{ str($loop->iteration)->padLeft(2, '0') }}
                                    </span>

                                    <span class="routine-player__exercise-content">
                                        <strong class="routine-player__exercise-name">
                                            {{ $stepName }}
                                        </strong>

                                        @if ($stepNotes)
                                            <small class="routine-player__exercise-notes">
                                                {{ $stepNotes }}
                                            </small>
                                        @endif
                                    </span>

                                    <span class="routine-player__exercise-metadata">
                                        <span>{{ $step->bpm }} BPM</span>

                                        @if ($durationLabel)
                                            <span>{{ $durationLabel }}</span>
                                        @endif
                                    </span>

                                    <x-heroicon-o-play
                                        class="routine-player__exercise-icon"
                                        width="18"
                                        height="18"
                                        aria-hidden="true"
                                    />
                                </button>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>
        </div>
    </main>
</x-layouts.dorelog>