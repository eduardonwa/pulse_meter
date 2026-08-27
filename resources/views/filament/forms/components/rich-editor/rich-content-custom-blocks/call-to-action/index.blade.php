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
    @class([
        'post--cta',
        "post--cta--{$type}",
    ])
>
    <div class="post--cta__content">
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

        <a class="button post--cta__button post--cta__button--{{ $buttonColor }}"
            href="{{ $buttonUrl }}"
        >
            {{ $button_label }}
        </a>
    </div>
</aside>
