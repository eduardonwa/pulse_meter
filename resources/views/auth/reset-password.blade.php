<x-layouts.dorelog>
    <main class="auth-page auth-page--centered">
        @if ($errors->any())
            <div class="badge badge--error">{{ $errors->first() }}</div>
        @endif
        
        <form class="auth-form auth-form--top | form-panel form" method="POST" action="{{ route('password.update') }}">
            @csrf

            <h1 class="heading-3">Reset your credentials</h1>
            <p>Choose a new password for your Dorelog account.</p>

            <div class="form-group">
                <input
                    type="hidden"
                    name="token"
                    value="{{ request()->route('token') }}"
                >
            
                <label for="email">Email</label>
            
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $request->email) }}"
                    autocomplete="email"
                    required
                >
            </div>
        
            <div class="form-group">
                <label for="password">New password</label>
            
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    required
                >
            </div>
        
            <div class="form-group">
                <label for="password_confirmation">Confirm new password</label>
            
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                >
            </div>
        
            <button class="button" type="submit">
                Reset password
            </button>
        </form>
    </main>
</x-layouts.dorelog>
