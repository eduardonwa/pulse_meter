<x-layouts.dorelog>
    <main class="auth-page verify-account">
        <header class="verify-account__header">
            <h1 class="heading-3">Verify your email</h1>
    
            <p>
                We sent a verification link to your email address.
            </p>
        </header>

        @if (session('status') === 'verification-link-sent')
            <p class="badge badge--verification-resent">
                A new verification link has been sent.
            </p>
        @endif

        <div class="verify-account__actions">
            <form class="form-panel" method="POST" action="{{ url('/email/verification-notification') }}">
                @csrf
    
                <button class="button" data-type="primary" type="submit">
                    Resend email
                </button>
            </form>
    
            <form class="form-panel" method="POST" action="{{ route('logout') }}">
                @csrf
    
                <button class="button" type="submit">
                    Log out
                </button>
            </form>
        </div>
    </main>
</x-layouts.dorelog>