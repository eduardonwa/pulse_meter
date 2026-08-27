@php
    $type = $type ?? 'subscription';
    $buttonColor = $button_color ?? 'dark';
    $stats = array_filter([
        $stat_1 ?? null,
        $stat_2 ?? null,
        $stat_3 ?? null,
    ]);
@endphp

<aside
    class="post--cta"
    data-cta-type="{{ $type }}"
    data-has-media="{{ $coverImageUrl ? 'true' : 'false' }}"
>
    @if ($type === 'resource' && $coverImageUrl)
        <div class="post--cta__media">
            <img
                class="post--cta__cover"
                src="{{ $coverImageUrl }}"
                alt="{{ $cover_alt ?? '' }}"
                loading="lazy"
            >
        </div>
    @endif

    <div class="post--cta__content">
        @if ($type === 'resource' && filled($eyebrow ?? null))
            <p class="post--cta__eyebrow">{{ $eyebrow }}</p>
        @endif

        <h2 class="post--cta__heading">
            {{ $heading }}
        </h2>

        @if ($type === 'resource')
            <p class="post--cta__description">{{ $description }}</p>

            @if ($stats !== [])
                <ul class="post--cta__stats">
                    @foreach ($stats as $stat)
                        <li>{{ $stat }}</li>
                    @endforeach
                </ul>
            @endif
        @else
            <div class="post--cta__benefits">
                {!! str($benefits ?? '')->sanitizeHtml() !!}
            </div>
        @endif

        <a
            class="button post--cta__button"
            data-button-color="{{ $buttonColor }}"
            href="{{ $button_url }}"
        >
            {{ $button_label }}
        </a>

        @if ($type === 'resource' && filled($supporting_text ?? null))
            <p class="post--cta__supporting-text">
                {{ $supporting_text }}
            </p>
        @endif
    </div>
</aside>
