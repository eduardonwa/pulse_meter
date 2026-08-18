@props([
    'template',
    'totalSeconds' => 0,
    'totalMinutes' => 0,
    'variant' => 'default',
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

        <dd class="value">
            {{ str($template->instrument)->title() }}
        </dd>
    </div>

    <div class="routine-facts__item">
        <dt class="label">
            Difficulty
        </dt>

        <dd class="value">
            {{ str($template->difficulty)->title() }}
        </dd>
    </div>

    @if ($totalSeconds > 0)
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

        <dd class="value">
            {{ $template->steps->count() }}
        </dd>
    </div>

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
</dl>