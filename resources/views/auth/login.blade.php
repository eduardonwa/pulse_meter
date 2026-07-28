<x-layouts.dorelog>
    <main class="auth-page">
        <h1>Log in to DoreLog</h1>

        @if ($errors->any())
            <div class="form-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">Email</label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                autofocus
            >

            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <label>
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                >

                Remember me
            </label>

            <button type="submit">
                Log in
            </button>
        </form>

        <p>
            No account yet?

            <a href="{{ route('register') }}">
                Register
            </a>
        </p>
    </main>
</x-layouts-dorelog>