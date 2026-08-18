@props([
    'template',
])

<section
    {{ $attributes->class([
        'routine-section',
        'routine-exercises',
    ]) }}
>
    <ul class="routine-exercises__list">
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

                    $duration < 60 =>
                        "{$duration} sec",

                    $duration % 60 === 0 =>
                        ((int) ($duration / 60)) . ' min',

                    default =>
                        intdiv($duration, 60)
                        . ' min '
                        . ($duration % 60)
                        . ' sec',
                };
            @endphp

            <li class="routine-exercise">
                <div class="routine-exercise__content">
                    <header class="routine-exercise__header">
                        <h4 class="routine-exercise__name">
                            {{ $stepName }}
                        </h4>

                        <div class="routine-exercise__metadata">
                            <span class="routine-exercise__bpm">
                                {{ $step->bpm }} BPM
                            </span>

                            @if ($durationLabel)
                                <span class="routine-exercise__duration">
                                    {{ $durationLabel }}
                                </span>
                            @endif
                            
                            <span class="routine-exercise__mode">
                                {{ str($step->mode)->title() }}
                            </span>
                        </div>
                    </header>

                    @if ($stepNotes)
                        <p class="routine-exercise__notes">
                            {{ $stepNotes }}
                        </p>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</section>