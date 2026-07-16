@props([
    'cards' => [],
    'summary' => [],
])

<header class="traffic-breakdown" aria-labelledby="classification-summary-title">
    <h2 class="traffic-breakdown__title" id="traffic-breakdown-title"> Session breakdown </h2>

    <section class="traffic-breakdown__shell">
        @foreach ($cards as $card)
            <div class="traffic-breakdown__stat">
                <h2 class="label">{{ $card['label'] }}</h2>
                <p class="card-key"> {{ $summary[$card['key']] ?? 0 }} </p>
                {{-- <p class="description"> {{ $card['description'] }} </p> --}}
            </div>
        @endforeach
    </section>
</header>