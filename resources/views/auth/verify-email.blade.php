<x-layouts.dorelog>
    <main class="auth-page">
        <h1>Verify your email</h1>

        <p>
            We sent a verification link to your email address.
        </p>

        @if (session('status') === 'verification-link-sent')
            <p>
                A new verification link has been sent.
            </p>
        @endif

        <form
            method="POST"
            action="{{ url('/email/verification-notification') }}"
        >
            @csrf

            <button type="submit">
                Send another verification email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit">
                Log out
            </button>
        </form>
    </main>
</x-layouts.dorelog>