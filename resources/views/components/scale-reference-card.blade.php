@props([
    'title',
    'description' => null,
    'notes' => [],
    'degrees' => [],
    'formula' => [],
])

<section
    {{ $attributes->merge(['class' => 'scale-reference-card']) }}
    x-data="{ scaleLabelMode: 'degrees' }"
>
    <div class="scale-reference-card__header">
        <div>
            <h2 class="scale-reference-card__title">{{ $title }}</h2>

            @if ($description)
                <p class="scale-reference-card__description">
                    {{ $description }}
                </p>
            @endif
        </div>

        <div class="scale-reference-card__switch" role="group" aria-label="Scale labels">
            <button
                type="button"
                class="scale-reference-card__switch-button"
                :class="{ 'is-active': scaleLabelMode === 'degrees' }"
                @click="scaleLabelMode = 'degrees'"
                :aria-pressed="scaleLabelMode === 'degrees'"
            >
                Degrees
            </button>

            <button
                type="button"
                class="scale-reference-card__switch-button"
                :class="{ 'is-active': scaleLabelMode === 'formula' }"
                @click="scaleLabelMode = 'formula'"
                :aria-pressed="scaleLabelMode === 'formula'"
            >
                Formula
            </button>
        </div>
    </div>

    <div class="scale-reference-card__notes" role="list" aria-label="Scale notes">
        @foreach ($notes as $index => $note)
            <div class="scale-reference-card__note" role="listitem">
                <span class="scale-reference-card__note-name">{{ $note }}</span>

                <span
                    class="scale-reference-card__note-label"
                    x-show="scaleLabelMode === 'degrees'"
                >
                    {{ $degrees[$index] ?? '—' }}
                </span>

                <span
                    class="scale-reference-card__note-label"
                    x-show="scaleLabelMode === 'formula'"
                    x-cloak
                >
                    {{ $formula[$index] ?? '—' }}
                </span>
            </div>
        @endforeach
    </div>
</section>

@once
    <style>
        [x-cloak] { display: none !important; }

        .scale-reference-card {
            --scale-card-bg: #252525;
            --scale-card-surface: #1d1d1d;
            --scale-card-border: #393939;
            --scale-card-text: #f4f4f4;
            --scale-card-muted: #9d9d9d;
            --scale-card-accent: #d8ff43;

            width: 100%;
            padding: 1.5rem;
            border: 1px solid var(--scale-card-border);
            border-radius: 1rem;
            background: var(--scale-card-bg);
            color: var(--scale-card-text);
        }

        .scale-reference-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .scale-reference-card__title {
            margin: 0;
            font-size: clamp(1.15rem, 2vw, 1.5rem);
            line-height: 1.2;
        }

        .scale-reference-card__description {
            max-width: 52rem;
            margin: .6rem 0 0;
            color: var(--scale-card-muted);
            line-height: 1.6;
        }

        .scale-reference-card__switch {
            display: inline-flex;
            flex-shrink: 0;
            padding: .2rem;
            border: 1px solid var(--scale-card-border);
            border-radius: 999px;
            background: var(--scale-card-surface);
        }

        .scale-reference-card__switch-button {
            appearance: none;
            border: 0;
            border-radius: 999px;
            padding: .45rem .75rem;
            background: transparent;
            color: var(--scale-card-muted);
            font: inherit;
            font-size: .8rem;
            cursor: pointer;
        }

        .scale-reference-card__switch-button.is-active {
            background: var(--scale-card-accent);
            color: #111;
        }

        .scale-reference-card__notes {
            display: grid;
            grid-template-columns: repeat(var(--scale-note-count, 8), minmax(3.25rem, 1fr));
            gap: .65rem;
            overflow-x: auto;
            padding-bottom: .25rem;
        }

        .scale-reference-card__note {
            min-width: 3.25rem;
            text-align: center;
        }

        .scale-reference-card__note-name {
            display: grid;
            place-items: center;
            width: 3rem;
            height: 3rem;
            margin-inline: auto;
            border: 1px solid var(--scale-card-border);
            border-radius: 50%;
            background: var(--scale-card-surface);
            font-weight: 700;
        }

        .scale-reference-card__note:first-child .scale-reference-card__note-name,
        .scale-reference-card__note:last-child .scale-reference-card__note-name {
            border-color: var(--scale-card-accent);
        }

        .scale-reference-card__note-label {
            display: block;
            margin-top: .65rem;
            color: var(--scale-card-muted);
            font-size: .8rem;
            font-weight: 700;
        }

        @media (max-width: 700px) {
            .scale-reference-card__header {
                flex-direction: column;
            }
        }
    </style>
@endonce
