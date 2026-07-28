<x-layouts.dorelog>
    <main class="profile-page | container">
        <h1>Profile</h1>

        <section>
            <h2>Account information</h2>

            <form
                method="POST"
                action="{{ route('user-profile-information.update') }}"
            >
                @csrf
                @method('PUT')

                <label for="name">Name</label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', auth()->user()->name) }}"
                    autocomplete="name"
                    required
                >

                @error('name', 'updateProfileInformation')
                    <p>{{ $message }}</p>
                @enderror

                <label for="email">Email</label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', auth()->user()->email) }}"
                    autocomplete="email"
                    required
                >

                @error('email', 'updateProfileInformation')
                    <p>{{ $message }}</p>
                @enderror

                <button type="submit">
                    Save profile
                </button>
            </form>
        </section>

        <section>
            <h2>Change password</h2>

            <form
                method="POST"
                action="{{ route('user-password.update') }}"
            >
                @csrf
                @method('PUT')

                <label for="current_password">
                    Current password
                </label>

                <input
                    id="current_password"
                    type="password"
                    name="current_password"
                    autocomplete="current-password"
                    required
                >

                @error('current_password', 'updatePassword')
                    <p>{{ $message }}</p>
                @enderror

                <label for="password">
                    New password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    required
                >

                @error('password', 'updatePassword')
                    <p>{{ $message }}</p>
                @enderror

                <label for="password_confirmation">
                    Confirm new password
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                >

                <button type="submit">
                    Update password
                </button>
            </form>
        </section>
    </main>
</x-layouts.dorelog>