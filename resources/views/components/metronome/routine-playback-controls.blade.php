<div class="control-actions">
    {{ $actions ?? '' }}
    
    <button
        class="button"
        data-type="primary"
        type="button"
        @click="startRoutineOver()"
    >
        Start over
    </button>
</div>