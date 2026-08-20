{{-- DESKTOP --}}
<section class="routine-player__details desktop-only">
    <h2 class="routine-player__section-title">
        About this routine
    </h2>

    @include('routines.partials.template-details-content')
</section>

{{-- MOBILE --}}
<div class="routine-player__details-mobile mobile-only" x-data="{ aboutModalOpen: false }">
    <button class="routine-player__details-button button"
        type="button"
        data-type="outline"
        @click="aboutModalOpen = true"
    >
        About this routine
    </button>

    <div class="modal-shell"
        x-cloak
        x-show="aboutModalOpen"
        x-trap.noscroll="aboutModalOpen"
        x-transition
        @keydown.escape.window="aboutModalOpen = false"
        @click.self="aboutModalOpen = false"
    >
        <div class="modal-panel" data-type="about-routine-template" @click.stop>
            <div class="header">
                <h2 class="heading">
                    About this routine
                </h2>

                <button class="button" type="button" data-type="icon" aria-label="Close" @click="aboutModalOpen = false">
                    <x-heroicon-o-x-circle
                        width="20"
                        height="20"
                        aria-hidden="true"
                    />
                </button>
            </div>

            <section class="routine-player__details">
                @include('routines.partials.template-details-content')
            </section>
        </div>
    </div>
</div>