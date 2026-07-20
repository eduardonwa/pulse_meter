@php
    $pageJourney = collect($session['page_journey'] ?? [])
        ->filter(
            fn (mixed $page): bool =>
                is_array($page)
                && is_string($page['path'] ?? null)
                && ($page['path'] ?? '') !== ''
        )
        ->values();

    $pageviewsCount = (int) (
        $session['pageviews_count'] ?? $pageJourney->count()
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

    <div class="user-journey__content"
        id="{{ $userJourneyContentId }}"
        x-show="open"
        x-cloak
    >
        @if ($pageJourney->isEmpty())
            <p class="session-entry__empty-message">
                No page journey was recorded for this visit.
            </p>
        @else
            <ol class="user-journey__steps">
                @foreach ($pageJourney as $page)                        
                    @php
                        $path = $page['path'] ?? '—';
                        
                        $pageTimestamp = $page['timestamp'] ?? null;

                        $pageTime =
                            \App\Services\UserDateFormatter::dateTimeParts(
                                $pageTimestamp,
                                auth()->user()
                            );
                            
                        $isEntrance = $loop->first;
                        $isExit = $loop->last;

                        $isPrevious =
                            ! $isEntrance
                            && ! $isExit
                            && $loop->index === $pageJourney->count() - 2;
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
                            <div class="user-journey__page-main">
                                <p class="user-journey__path">
                                    {{ $path }}
                                </p>

                                <time class="user-journey__time" datetime="{{ $page['timestamp'] ?? '' }}">
                                    {{ $pageTime['time'] ?? '—' }}
                                </time>
                            </div>

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