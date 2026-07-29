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

        <form class="auth-form | form-panel form" method="POST" action="{{ route('login') }}">
            @csrf
    
            <h1 class="heading-3">Log in to DoreLog</h1>

            <div class="form-group">
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
            </div>

            <div class="form-group">
                <label for="password">Password</label>
    
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <label>
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                >

                Remember me
            </label>

            <button class="button" type="submit">
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