<nav class="account-page__navigation" aria-label="Account">
    <a data-type="outline" data-variant="link" href="{{ route('profile.edit') }}" wire:navigate
        @class([
            'button',
            'is-selected' => request()->routeIs('profile.edit'),
        ])    
        @if (request()->routeIs('profile.edit'))
            aria-current="page"
        @endif
    >
        Profile
    </a>

    <a  data-type="outline" data-variant="link" href="{{ route('billing.index') }}" wire:navigate
        @class([
            'button',
            'is-selected' => request()->routeIs('billing.index'),
        ])
        @if (request()->routeIs('billing.index'))
            aria-current="page"
        @endif
    >
        Plans &amp; billing
    </a>
    <a data-type="outline" data-variant="link" href="{{ route('account.my-routines') }}" wire:navigate
        @class(['button', 'is-selected' => request()->routeIs('account.my-routines')])
        @if (request()->routeIs('account.my-routines')) aria-current="page" @endif
    >My Routines</a>
</nav>