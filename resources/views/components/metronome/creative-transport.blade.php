@props(['variant' => 'mobile'])

<div class="creative-transport creative-transport--{{ $variant }}" aria-label="Creative playback controls">
    <div class="creative-transport__tempo">
        <x-inputs.number-picker
            class="creative-transport__bpm"
            options="bpmOptions"
            model="metronome.bpm"
            after-change="handleCreativeBpmChange()"
            hint="Scroll to change tempo"
            :controls="$variant === 'desktop'"
            :live-preview="$variant !== 'desktop'"
        />
        <span class="creative-transport__unit">BPM</span>
    </div>

    <div class="creative-transport__play">
        <button
            class="button uppercase"
            data-type="play-metronome"
            type="button"
            :data-state="isPlaying ? 'stop' : 'start'"
            @click="toggle()"
        >
            <span x-text="isPlaying ? 'Stop' : 'Start'">Start</span>
        </button>
    </div>
</div>
