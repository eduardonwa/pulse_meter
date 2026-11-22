@props([
    'groups',
    'beatAction' => null,
    'subdivisionAction' => null,
])

<div {{ $attributes->class(['time-signature__beats']) }}>
    <h2 class="heading" style="font-size: 14px;">Beats</h2>

    <div class="beat-groups">
        <template
            x-for="(group, groupIndex) in {{ $groups }}"
            :key="groupIndex"
        >
            <div
                class="beat-group badge"
                :class="[
                    'badge--group-beat-a',
                    'badge--group-beat-b',
                    'badge--group-beat-c',
                    'badge--group-beat-d'
                ][groupIndex % 4]"
            >
                <template x-for="item in group" :key="`${item.beat}-${item.subdivisionIndex ?? 'beat'}`">
                    <div class="beat-unit">
                        <button
                            class="beat-mark"
                            :class="{
                                'subdivision-mark': item.subdivisionIndex !== null,
                                'is-group-start': item.groupStart,
                                'is-active': currentBeat === item.beat &&
                                    currentSubdivision === (item.subdivisionIndex === null ? 0 : item.subdivisionIndex + 1),
                                'is-accent': item.sound === 'accent',
                                'is-click': item.sound === 'click',
                                'is-rest': item.sound === 'rest'
                            }"
                            type="button"
                            @click="item.subdivisionIndex === null
                                ? {{ $beatAction ?? "null" }}
                                : {{ $subdivisionAction ? str_replace("subdivisionIndex", "item.subdivisionIndex", $subdivisionAction) : "null" }}"
                        >
                            <span x-text="item.sound === 'accent' ? 'A' : item.sound === 'click' ? 'C' : 'R'"></span>
                            <small x-text="item.subdivisionIndex === null ? item.beat : item.label"></small>
                        </button>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
