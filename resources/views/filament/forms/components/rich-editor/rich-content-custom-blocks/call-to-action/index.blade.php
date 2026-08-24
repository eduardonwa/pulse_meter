<aside class="post-cta">
    <div class="post-cta__content">
        <h2 class="post-cta__heading">
            {{ $heading }}
        </h2>

        <div class="post-cta__benefits">
            {!! str($benefits)->sanitizeHtml() !!}
        </div>

        <a href="{{ $button_url }}" class="post-cta__button">
            {{ $button_label }}
        </a>
    </div>
</aside>