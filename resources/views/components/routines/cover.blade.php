@props([
    'routine',
    'template',
    'coverUrl' => null,
    'typeLabel',
    'totalSeconds' => 0,
    'totalMinutes' => 0,
])

<article {{ $attributes->class('routine-cover') }}>
    <div class="routine-cover__media">
        @if ($coverUrl)
            <img
                class="routine-cover__image"
                src="{{ $coverUrl }}"
                alt="{{ $routine->cover_alt ?: $routine->title }}"
                width="800"
                height="1000"
            >
        @else
            <div
                class="routine-cover__placeholder"
                aria-hidden="true"
            ></div>
        @endif
    </div>

    <div class="routine-cover__content">
        <div class="routine-cover__badges">
            <span class="badge badge--routine-type">
                {{ $typeLabel }}
            </span>

            <span class="badge badge--routine-instrument">
                {{ str($template->instrument)->title() }}
            </span>

            <span class="badge badge--routine-difficulty">
                {{ str($template->difficulty)->title() }}
            </span>
        </div>

        <header class="routine-cover__header">
            <h1 class="title">
                {{ $routine->title }}
            </h1>

            <p class="summary">
                {{ $routine->summary }}
            </p>
        </header>
{{-- 
        <x-routines.facts
            :template="$template"
            :total-seconds="$totalSeconds"
            :total-minutes="$totalMinutes"
            variant="cover"
        /> --}}

        <button class="button" data-type="icon-text" type="button" aria-controls="routine-details-drawer" :aria-expanded="drawerOpen.toString()" @click="drawerOpen = true">
            <x-heroicon-o-queue-list />

            <span>View routine</span>
        </button>
    </div>
</article>