<x-layouts.dorelog>
    <main class="account-page | container">
        <x-account.navigation />

        <header class="account-page__header">
            <p>Manage the routines you've shared and saved from other creators.</p>
        </header>

        <section class="account-page__section">
            <h2 class="heading-3">Shared by me</h2>

            @forelse ($published as $share)
                <div class="form-panel" style="margin-bottom: 1rem;">
                    <strong>{{ $share->routine?->name }}</strong>
                    <p>{{ min(10, $share->routine?->steps->count() ?? 0) }} shared exercises · {{ $share->is_active ? 'Active' : 'Inactive' }}</p>
                    @if ($share->is_active)
                        <a class="button" data-type="outline" href="{{ route('shared.show', ['locale' => app()->getLocale(), 'token' => $share->token]) }}">View</a>
                    @endif
                    @if ($share->is_active)
                        <button class="button" data-type="outline" type="button" onclick="copySharedRoutineLink(@js(route('shared.show', ['locale' => app()->getLocale(), 'token' => $share->token])))">Copy link</button>
                    @endif
                    <form method="POST" action="{{ route('shared.toggle', $share) }}" style="display:inline;">
                        @csrf
                        @method('PATCH')
                        <button class="button" data-type="outline" type="submit" @if ($share->is_active) onclick="return confirm('Deactivate this link? Imported copies will not be affected.')" @endif>{{ $share->is_active ? 'Deactivate' : 'Activate' }}</button>
                    </form>
                </div>
            @empty
                <p>You haven't shared any routines yet.</p>
            @endforelse

            @if ($canPublish)
                <h3>Share a routine</h3>
                @foreach ($myRoutines as $personal)
                    @if (! $personal->sharedPublication)
                        <form method="POST" action="{{ route('shared.store', $personal) }}" style="margin-bottom: .75rem;">
                            @csrf
                            <button class="button" data-type="outline" type="submit">Share {{ $personal->name }}</button>
                        </form>
                    @endif
                @endforeach
            @endif
        </section>

        <section class="account-page__section">
            <h2 class="heading-3">Shared with me</h2>
            @forelse ($imported as $copy)
                <div class="form-panel" style="margin-bottom: 1rem;">
                    <strong>{{ $copy->name }}</strong>
                    <p>{{ $copy->steps()->count() }} exercises</p>
                    @if ($copy->importedFrom?->is_active)
                        <a class="button" data-type="outline" href="{{ route('shared.show', ['locale' => app()->getLocale(), 'token' => $copy->importedFrom->token]) }}">View original</a>
                    @else
                        <span>Link inactive</span>
                    @endif
                </div>
            @empty
                <p>No imported shared routines yet.</p>
            @endforelse
        </section>
    </main>
    <script>
        async function copySharedRoutineLink(url) {
            if (navigator.clipboard?.writeText) {
                try {
                    await navigator.clipboard.writeText(url);
                    return;
                } catch (error) {
                    // Fall back for environments without Clipboard API permission.
                }
            }

            const field = document.createElement('textarea');
            field.value = url;
            field.style.position = 'fixed';
            field.style.opacity = '0';
            document.body.appendChild(field);
            field.select();
            try {
                document.execCommand('copy');
            } finally {
                field.remove();
            }
        }
    </script>
</x-layouts.dorelog>
