<div class="modes" x-show="activeTab === 'sessions'" x-cloak>
    <div class="header">
        <h2>type</h2>
        
        <label for="mode">
            <select class="mode-selector" x-model="metronome.mode"
                @change="trackSessionTypeSelected($event.target.value); handleSessionModeChange($event.target.value);
                    $event.target.value === 'timer' && $nextTick(() => {
                        requestAnimationFrame(() => {
                            window.dispatchEvent(new Event('picker:sync'))
                        })})"
            >
                <option value="classic">Classic</option>
                    @can('use-pro')
                        <option value="creative">Creative</option>
                    @endcan
                <option value="timer">Timer</option>
            </select>
        </label>
    </div>

    @include('components.metronome.sessions.timer')

    @include('components.metronome.sessions.recent')

    @can('use-pro')
        @include('components.metronome.sessions.creative')
    @endcan
</div>