<button
    type="button"
    class="button"
    data-type="outline"
    x-data="alphaTabCountInControl()"
    @alphatab:count-in-changed.window="enabled = Boolean($event.detail.enabled)"
    @click.stop="toggle()"
    :aria-pressed="enabled.toString()"
    :title="enabled ? 'Disable count-in' : 'Enable count-in'"
    x-text="enabled ? 'Count-in: On' : 'Count-in: Off'"
>Count-in: On</button>
