@props([
    'template' => null,
    'totalSeconds' => 0,
    'totalMinutes' => 0,
    'variant' => 'default',
    'dynamic' => false,
])

<dl
    {{ $attributes->class([
        'routine-facts',
        "routine-facts--{$variant}",
    ]) }}
>
    <div class="routine-facts__item">
        <dt class="label">
            Instrument
        </dt>

        <dd
            class="value"
            @if ($dynamic)
                x-text="selectedRoutine?.instrument"
            @endif
        >
            @if (! $dynamic)
                {{ str($template->instrument)->title() }}
            @endif
        </dd>
    </div>

    <div class="routine-facts__item">
        <dt class="label">
            Difficulty
        </dt>

        <dd
            class="value"
            @if ($dynamic)
                x-text="selectedRoutine?.difficulty"
            @endif
        >
            @if (! $dynamic)
                {{ str($template->difficulty)->title() }}
            @endif
        </dd>
    </div>

    @if ($dynamic)
        <template x-if="selectedRoutine?.totalSeconds > 0">
            <div class="routine-facts__item">
                <dt class="label">
                    Duration
                </dt>

                <dd
                    class="value"
                    x-text="`${selectedRoutine.totalMinutes} minutes`"
                ></dd>
            </div>
        </template>
    @elseif ($totalSeconds > 0)
        <div class="routine-facts__item">
            <dt class="label">
                Duration
            </dt>

            <dd class="value">
                {{ $totalMinutes }} minutes
            </dd>
        </div>
    @endif

    <div class="routine-facts__item">
        <dt class="label">
            Exercises
        </dt>

        <dd class="value"
            @if ($dynamic)
                x-text="selectedRoutine?.exercisesCount"
            @endif
        >
            @if (! $dynamic)
                {{ $template->steps->count() }}
            @endif
        </dd>
    </div>

    @if ($dynamic)
        <template x-if="selectedRoutine?.type === 'weekly_challenge' &&selectedRoutine?.challengeDays">
            <div class="routine-facts__item">
                <dt class="label">
                    Challenge
                </dt>

                <dd class="value" x-text="`${selectedRoutine.challengeDays} days`"></dd>
            </div>
        </template>

        <template x-if=" selectedRoutine?.type === 'weekly_challenge' && selectedRoutine?.recommendedSessions " >
            <div class="routine-facts__item">
                <dt class="label">
                    Sessions
                </dt>

                <dd class="value" x-text="`${selectedRoutine.recommendedSessions} recommended`"></dd>
            </div>
        </template>
    @else
        @if (
            $template->type === 'weekly_challenge'
            && $template->challenge_days
        )
            <div class="routine-facts__item">
                <dt class="label">
                    Challenge
                </dt>

                <dd class="value">
                    {{ $template->challenge_days }} days
                </dd>
            </div>
        @endif

        @if (
            $template->type === 'weekly_challenge'
            && $template->recommended_sessions
        )
            <div class="routine-facts__item">
                <dt class="label">
                    Sessions
                </dt>

                <dd class="value">
                    {{ $template->recommended_sessions }}
                    recommended
                </dd>
            </div>
        @endif
    @endif
</dl>