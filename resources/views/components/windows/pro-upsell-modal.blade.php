@props([
    'viewerType',
])

@php
    $upgradeUrl = $viewerType === 'guest'
        ? route('register')
        : route('billing.index');
@endphp

<dialog
    class="dialog-shell"
    x-data="{ isOpen: false }"
    x-on:open-pro-upsell.window="
        isOpen = true;
        $nextTick(() => {
            if (!$el.open) {
                $el.showModal();
            }
        });
    "
    x-on:close-pro-upsell.window="
        isOpen = false;
        if ($el.open) {
            $el.close();
        }
    "
    @cancel.prevent="
        isOpen = false;
        $el.close();
    "
>
    <header class="dialog-shell__heading">
        <strong>Save routines with Pro</strong>
    </header>

    <div class="dialog-shell__content">
        <p>
            Upgrade to Pro to save a copy of this routine
            and make it your own.
        </p>

        <ul>
            <li>Save shared routines</li>
            <li>Create unlimited routines</li>
            <li>Create unlimited playlists</li>
        </ul>
    </div>

    <footer>
        <button
            class="button"
            data-type="secondary"
            type="button"
            @click="
                isOpen = false;
                $el.closest('dialog').close();
            "
        >
            Not now
        </button>

        <a
            class="button"
            data-type="primary"
            href="{{ $upgradeUrl }}"
        >
            {{ $viewerType === 'guest'
                ? 'Create an account'
                : 'Upgrade to Pro'
            }}
        </a>
    </footer>
</dialog>