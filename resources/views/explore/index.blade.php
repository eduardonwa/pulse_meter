<x-layouts.dorelog title="Explore | Dorelog" description="Discover guitar practice routines and creative tools.">
    <main class="container explore-hub">
        <h1 class="heading-3">Explore</h1>

        <div class="explore-hub__grid">
            <a class="explore-hub__card" href="{{ route('routines.index', ['locale' => app()->getLocale()]) }}">
                <div class="explore-hub__card-main">
                    <h2>Routines</h2>
                    <p>Discover routines and bring them into your practice.</p>
                </div>

                <div class="explore-hub__card-fold">
                    <x-heroicon-o-queue-list width="24" height="24" aria-hidden="true" />
                    <span class="explore-hub__card-action">
                        Browse routines
                        <x-heroicon-o-arrow-right width="18" height="18" aria-hidden="true" />
                    </span>
                </div>
            </a>

            @can('use-pro')
                <a class="explore-hub__card" href="{{ route('welcome', ['panel' => 'creative']) }}">
                    <div class="explore-hub__card-main">
                        <h2>Creative Tools</h2>
                        <p>Pulse Editor and Randomizer.</p>
                    </div>

                    <div class="explore-hub__card-fold">
                        <x-heroicon-o-sparkles width="24" height="24" aria-hidden="true" />
                        <span class="explore-hub__card-action">
                            Open tools
                            <x-heroicon-o-arrow-right width="18" height="18" aria-hidden="true" />
                        </span>
                    </div>
                </a>
            @endcan
        </div>
    </main>
</x-layouts.dorelog>
