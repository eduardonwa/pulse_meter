<x-layouts.dorelog>
    <main class="account-page | container"
        x-data="{
            confirmModal: { isOpen: false, title: '', message: '', confirmLabel: '' },
            pendingUnshareForm: null,
            openUnshareModal(event) {
                this.pendingUnshareForm = event.currentTarget.closest('form');
                this.confirmModal.title = 'Make this routine private?';
                this.confirmModal.message = 'The shared link will no longer be accessible. Imported copies will not be affected.';
                this.confirmModal.confirmLabel = 'Unshare';
                this.confirmModal.isOpen = true;
            },
            closeConfirmModal() {
                this.confirmModal.isOpen = false;
                this.pendingUnshareForm = null;
            },
            confirmModalAction() {
                if (!this.pendingUnshareForm) return;
                this.pendingUnshareForm.submit();
            },
            toast: { visible: false, message: '', type: 'success', timer: null },
            showToast(message) {
                clearTimeout(this.toast.timer);
                this.toast.message = message;
                this.toast.type = 'success';
                this.toast.visible = true;
                this.toast.timer = setTimeout(() => this.hideToast(), 3000);
            },
            hideToast() {
                clearTimeout(this.toast.timer);
                this.toast.visible = false;
            }
        }"
        @shared-link-copied.window="showToast('Copied URL')"
    >
        <x-account.navigation />
        <x-toaster />

        <header class="account-page__header">
            <p>Manage your routines and imported copies.</p>
        </header>

        <section class="account-page__section">
            <h2 class="heading-3">My Routines</h2>

            @forelse ($myRoutines as $personal)
                @php($share = $personal->sharedPublication)
                <div class="shared-routine | flow">
                    <h2 class="shared-routine__name">
                        {{ $personal->name }}
                        @if ($share)
                            <span class="badge {{ $share->is_active ? 'badge--shareable-routine' : 'badge--neutral' }}">
                                {{ $share->is_active ? 'Public' : 'Private' }}
                            </span>
                        @endif
                    </h2>

                    <p class="shared-routine__exercises">{{ min(10, $personal->steps->count()) }} exercises</p>

                    <div class="shared-routine__actions">
                        @if ($share?->is_active)
                            <a class="button view" data-type="icon-text" href="{{ $share->publicUrl(app()->getLocale()) }}">
                                <x-heroicon-o-eye width="20" height="20" />
                                View
                            </a>

                            <button class="button copy" data-type="icon-text" type="button" onclick="copySharedRoutineLink(@js($share->publicUrl(app()->getLocale())))">
                                <x-heroicon-o-clipboard width="20" height="20" />
                                Copy link
                            </button>
                        @endif

                        @if ($share)
                            <form method="POST" action="{{ route('shared.toggle', $share) }}">
                                @csrf
                                @method('PATCH')
                                <button class="button {{ $share->is_active ? 'unshare' : 'share' }}" data-type="icon-text"
                                    type="{{ $share->is_active ? 'button' : 'submit' }}"
                                    @if ($share->is_active)
                                        @click="openUnshareModal($event)"
                                    @endif
                                >
                                    @if ($share->is_active)
                                        <x-heroicon-o-lock-closed width="20" height="20"/>
                                    @else
                                        <x-heroicon-o-lock-open width="20" height="20"/>
                                    @endif
                                    {{ $share->is_active ? 'Unshare' : 'Share' }}
                                </button>
                            </form>
                        @elseif ($canPublish)
                            <form method="POST" action="{{ route('shared.store', $personal) }}">
                                @csrf
                                <button class="button share" data-type="icon-text" type="submit">
                                    <x-heroicon-o-lock-open width="20" height="20" />
                                    Share
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p>You haven't created any routines yet.</p>
            @endforelse
        </section>

        <section class="account-page__section">
            <h2 class="heading-3">Imported Routines</h2>
            @forelse ($imported as $copy)
                <div class="form-panel" style="margin-bottom: 1rem;">
                    <h2>{{ $copy->name }}</h2>

                    <p>{{ $copy->steps()->count() }} exercises</p>
                    @if ($copy->importedFrom?->is_active)
                        <a class="button" data-type="outline" href="{{ $copy->importedFrom->publicUrl(app()->getLocale()) }}">
                            View original
                        </a>
                    @else
                        <span>Link inactive</span>
                    @endif
                </div>
            @empty
                <p>No imported shared routines yet.</p>
            @endforelse
        </section>
        <x-windows.confirm-modal />
    </main>
    
    <script>
        async function copySharedRoutineLink(url) {
            if (navigator.clipboard?.writeText) {
                try {
                    await navigator.clipboard.writeText(url);
                    window.dispatchEvent(new Event("shared-link-copied"));
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
                if (document.execCommand('copy')) {
                    window.dispatchEvent(new Event('shared-link-copied'));
                }
            } finally {
                field.remove();
            }
        }
    </script>
</x-layouts.dorelog>
