
@props(['routeName'])

<div class="lang" x-data="{ dropdownOpen: false }">
    <button
        type="button"
        class="button"
        data-type="icon-text"
        :aria-expanded="dropdownOpen.toString()"
        aria-haspopup="true"
        @click="dropdownOpen = !dropdownOpen"
        @keydown.escape.window="dropdownOpen = false"
        @click.outside="dropdownOpen = false"
    >
        <x-heroicon-o-language />
        Language:
        {{ app()->getLocale() === 'es' ? 'Español' : 'English' }}
        <x-heroicon-m-chevron-down />
    </button>

    <div class="lang__options" x-show="dropdownOpen" x-cloak>
        <a class="option" href="{{ route($routeName, ['locale' => 'en']) }}">
            English
        </a>

        <a class="option" href="{{ route($routeName, ['locale' => 'es']) }}">
            Español
        </a>
    </div>
</div>
