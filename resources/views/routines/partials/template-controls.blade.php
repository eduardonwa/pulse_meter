@if (! in_array($viewerType, ['guest', 'free'], true))
    <div class="routine-player__controls" x-data="{ controlsExposed: true }">
        <header class="header">
            <span class="header__label">Controls</span>

            <button class="button" data-type="icon" type="button"
                :aria-expanded="controlsExposed.toString()"
                @click="controlsExposed = !controlsExposed"
            >
                <x-heroicon-o-chevron-down
                    x-bind:class="{ 'rotate-180': controlsExposed }"
                />
            </button>
        </header>

        <div class="routine-player__controls-body" x-show="controlsExposed" x-cloak>
            <div class="save-routine">
                {{-- SAVE / OWNERSHIP --}}
                @if ($viewerHasTemplate)
                    <button class="button disabled" data-type="icon-text" type="button" disabled>
                        <x-heroicon-o-check
                            width="14"
                            height="14"
                            aria-hidden="true"
                        />
        
                        <span>In your routines</span>
                    </button>
        
                @elseif ($viewerType === 'trial')
                    <button class="button" data-type="icon-text" type="button" @click="$dispatch('open-pro-upsell')">
                        <x-heroicon-o-bookmark
                            width="14"
                            height="14"
                            aria-hidden="true"
                        />
        
                        <span>Save a copy</span>
        
                        <span class="badge badge--upsell-pro">
                            Pro
                        </span>
                    </button>
        
                @elseif (in_array($viewerType, ['pro', 'lifetime'], true))
                    <button class="button" data-type="icon-text" type="button" @click="saveTemplateCopy()">
                        <x-heroicon-o-bookmark
                            width="14"
                            height="14"
                            aria-hidden="true"
                        />
        
                        <span>Save a copy</span>
                    </button>
                @endif
            </div>
    
            {{-- PATTERN PLAYBACK --}}
            @if ($template->steps->contains(fn ($step) => filled($step->alpha_tex)))
                <x-metronome.pattern-loop-control />
            @endif
        </div>
    </div>
@endif