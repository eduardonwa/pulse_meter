<x-layouts.dorelog>
    <main class="routine-player"
        x-data="routinePlayer(@js($practiceContext), @js($pulsePresets))"
        @keydown.window="handleKeydown($event)"
        data-template-title="{{ $original->name }}"
        data-use-template-url="{{ route('welcome') }}"
        @if (in_array($viewerType, ['pro', 'lifetime'], true))
            data-save-template-copy-url="{{ route('shared.save-copy', $publication) }}"
        @endif
    >
        <div class="routine-player__container container">
            <nav class="routine-detail__navigation" aria-label="Shared routines">
                <a class="button" data-type="icon-text" href="{{ auth()->check() ? route('account.my-routines') : route('routines.index', ['locale' => app()->getLocale()]) }}">
                    <x-heroicon-o-arrow-left width="18" height="18" aria-hidden="true" />
                    <span>{{ auth()->check() ? 'My Routines' : 'Routines' }}</span>
                </a>
            </nav>

            <header class="routine-player__header">
                <h1 class="heading-3">{{ $original->name }}</h1>
                <p>Shared by {{ $publication->user->name }}</p>
            </header>

            <div class="routine-player__layout">
                <aside class="routine-player__sidebar">
                    @include('routines.partials.template-player')
                    @include('routines.partials.template-controls')
                    @include('routines.partials.template-details')
                </aside>
                @include('routines.partials.template-exercises')
            </div>
        </div>

        <x-windows.advance-modal />
        <x-windows.confirm-modal />
    </main>
    <x-windows.pro-upsell-modal :viewer-type="$viewerType" />
</x-layouts.dorelog>
