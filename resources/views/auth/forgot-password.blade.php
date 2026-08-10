<x-layouts.dorelog>
    <main class="auth-page auth-page--centered">
        @if (session('status'))
            <div class="badge badge--trial-register">{{ session('status') }}</div>
        @endif
        
        @if ($errors->any())
            <div class="badge badge--error">{{ $errors->first() }}</div>
        @endif
        
        <form class="auth-form auth-form--top | form-panel form" method="POST" action="{{ route('password.email') }}">
            @csrf
            
            <h1 class="heading-3">Account recovery</h1>
            <p class="fs-300">Enter your email and we'll send you a link to reset your password.</p>

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
        
            <button class="button" type="submit">
                Send password reset link
            </button>
        </form>
    </main>
</x-layouts.dorelog>