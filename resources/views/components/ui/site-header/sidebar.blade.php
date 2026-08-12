<aside class="sidebar"
    id="sidebar"
    x-show="sidebar"
    x-cloak
    x-trap.noscroll="sidebar"
    @keydown.escape.window="sidebar = false"
    @click.outside="sidebar = false"
>
    <nav class="sidebar__menu">
        @guest
            <a class="button" data-type="icon" href="{{ route('login') }}" wire:navigate>
                <x-heroicon-o-user />

                <span>Log in</span>
            </a>
            @if (Route::has('register'))
                <a class="button" data-type="icon" href="{{ route('register') }}" wire:navigate>
                    <x-heroicon-o-cursor-arrow-rays />

                    <span>Register</span>
                </a>
            @endif
        @endguest

        @auth
            <a class="button" data-type="icon" href="{{ route('profile.edit') }}">
                <x-heroicon-o-user />
                
                <span>My Account</span>
            </a>

            <form class="logout" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="button" data-type="icon" type="submit">
                    <x-heroicon-o-arrow-right-start-on-rectangle />
                    
                    Log out
                </button>
            </form>
        @endauth

        {{-- links --}}
        <div class="links">
            <a class="button display-none--on-desktop" data-type="icon" href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}">
                <x-heroicon-o-user-group />
                Blog
            </a>

            <a class="button" data-type="icon" href="mailto:{{ config('mail.support_address') }}">
                <x-heroicon-o-lifebuoy />

                <span>Contact Support</span>
            </a>
        </div>
    </nav>
</aside>