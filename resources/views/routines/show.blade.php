@php
    $template = $routine->routineTemplate;

    $totalSeconds = $template->steps->sum(
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
@endphp

<x-layouts.dorelog>
    <main class="routine-detail"
        x-data="{ drawerOpen: false }"
        @keydown.escape.window="drawerOpen = false"
    >
        <div class="routine-detail__container container">
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

            <x-routines.cover
                :routine="$routine"
                :template="$template"
                :cover-url="$coverUrl"
                :type-label="$typeLabel"
                :total-seconds="$totalSeconds"
                :total-minutes="$totalMinutes"
            />
        </div>

        <x-routines.drawer
            :routine="$routine"
            :template="$template"
            :type-label="$typeLabel"
            :total-seconds="$totalSeconds"
            :total-minutes="$totalMinutes"
        />
    </main>
</x-layouts.dorelog>