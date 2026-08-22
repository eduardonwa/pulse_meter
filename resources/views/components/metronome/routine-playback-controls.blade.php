<div class="control-settings" x-cloak
    x-show="steps.some(step => !!step.alpha_tex)"
>
    <x-metronome.pattern-loop-control
        x-show="steps.some(step => !!step.alpha_tex)"
        x-cloak
    />
</div>

<div class="control-actions">
    {{ $actions ?? '' }}
    
    <button
        class="button"
        data-type="secondary"
        type="button"
        @click="startRoutineOver()"
    >
        Start over
    </button>
</div>