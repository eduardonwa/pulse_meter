<div {{ $attributes->class([
    'horizontal-nav',
    'display-none--until-desktop',
]) }}>
    <div class="links">
        <a class="button" href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}">
            Blog
        </a>
    </div>

    <div class="auth">
        {{ $actions }}
    </div>
</div>