<x-layouts.dorelog>
    <main class="auth-page auth-page--centered">
        @if ($errors->any())
            <div class="form-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="auth-form | form-panel form" method="POST" action="{{ route('register') }}">
            @csrf

            <h1 class="heading-3">Create your DoreLog account</h1>

            <a class="button" data-type="google-auth" href="{{ route('auth.google') }}">
                <x-ui.google-icon />
                Continue with Google
            </a>

            <div class="auth-separator">
                <hr>
                <span>or</span>
                <hr>
            </div>

            <div class="form-group">
                <label for="name">Username</label>
    
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    autocomplete="name"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                >
            </div>
    
            <div class="form-group">
                <label for="password">Password</label>
    
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password_confirmation">
                    Confirm password
                </label>
    
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button class="button" type="submit">
                Create account
            </button>
        </form>

        <p>
            Already registered?

            <a href="{{ route('login') }}">
                Log in
            </a>
        </p>
    </main>
</x-layouts.dorelog>