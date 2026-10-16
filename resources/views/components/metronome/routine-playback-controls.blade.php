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