@php
    $pagePaths = collect($session['page_paths'] ?? [])
        ->filter(
            fn (mixed $path): bool =>
                is_string($path) && $path !== ''
        )
        ->values();

    $pageviewsCount = (int) (
        $session['pageviews_count'] ?? $pagePaths->count()
    );

    $entrancePath = $session['entrance_path'] ?? null;
    $previousPath = $session['previous_path'] ?? null;
    $exitPath = $session['exit_path'] ?? null;

    $userJourneyContentId = $sessionId . '-user-journey-content';
@endphp

<section class="user-journey">
    <x-collapse-toggle
        label-class="collapse-toggle__label"
        label="User Journey"
        :controls="$userJourneyContentId"
    />

    <div class="user-journey__summary">
        <span class="page-views">
            {{ $pageviewsCount }}
            {{ \Illuminate\Support\Str::plural('pageview', $pageviewsCount) }}
        </span>

        <span class="duration">
            {{ \App\Services\UserDateFormatter::duration(
                $session['duration_seconds'] ?? 0
            ) }}
        </span>
    </div>

    <div class="user-journey__content"
        id="{{ $userJourneyContentId }}"
        x-show="open"
        x-cloak
    >
        @if ($pagePaths->isEmpty())
            <p class="session-entry__empty-message">
                No page journey was recorded for this visit.
            </p>
        @else
            <ol class="user-journey__steps">
                @foreach ($pagePaths as $path)
                    @php
                        $isEntrance = $loop->first;
                        $isExit = $loop->last;

                        $isPrevious =
                            ! $isEntrance
                            && ! $isExit
                            && $loop->index === $pagePaths->count() - 2;
                    @endphp

                    <li
                        @class([
                            'user-journey__step',
                            'user-journey__step--entrance' => $isEntrance,
                            'user-journey__step--previous' => $isPrevious,
                            'user-journey__step--exit' => $isExit,
                        ])
                    >
                        <div class="user-journey__marker"> <span>{{ $loop->iteration }}</span> </div>

                        <div class="user-journey__page">
                            <p class="user-journey__path"> {{ $path }} </p>

                            <div class="user-journey__badges">
                                @if ($isEntrance)
                                    <span class="user-journey__badge">
                                        Entrance
                                    </span>
                                @endif

                                @if ($isPrevious)
                                    <span class="user-journey__badge">
                                        Previous
                                    </span>
                                @endif

                                @if ($isExit)
                                    <span class="user-journey__badge">
                                        Exit
                                    </span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</section>