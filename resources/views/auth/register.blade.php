<x-layouts.dorelog>
    <main class="auth-page">
        <h1>Create your DoreLog account</h1>

        @if ($errors->any())
            <div class="form-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label for="name">Name</label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                autocomplete="name"
                required
                autofocus
            >

            <label for="email">Email</label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="new-password"
                required
            >

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

            <button type="submit">
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