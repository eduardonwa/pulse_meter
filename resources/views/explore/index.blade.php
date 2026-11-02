<x-layouts.dorelog title="Explore | Dorelog" description="Discover guitar practice routines and creative tools.">
    <main class="container explore-hub">
        <h1 class="heading-3">Explore</h1>
        <div class="explore-hub__grid">
            <a class="explore-hub__card" href="{{ route('routines.index', ['locale' => app()->getLocale()]) }}">
                <x-heroicon-o-queue-list width="28" height="28" />
                <h2>Routines</h2>
                <p>Discover routines and bring them into your practice.</p>
            </a>
            @can('use-pro')
                <a class="explore-hub__card" href="{{ route('welcome', ['panel' => 'creative']) }}">
                    <x-heroicon-o-sparkles width="28" height="28" />
                    <h2>Creative Tools</h2>
                    <p>Pulse Editor and Randomizer.</p>
                </a>
            @endcan
        </div>
    </main>
</x-layouts.dorelog>
