@props([
    'groups',
    'interactive' => false,
])

<div {{ $attributes->class(['time-signature__beats']) }}>
    <h2 class="heading">Beats</h2>

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
                <template x-for="item in group" :key="item.beat">
                    <div class="beat-unit">
                        <button
                            class="beat-mark"
                            type="button"
                            @if (! $interactive)
                                aria-disabled="true"
                            @endif
                            @if ($interactive)
                                @click="applyEditorTool(item.beat)"
                            @endif
                            :class="{
                                'is-group-start': item.groupStart,
                                'is-active': currentBeat === item.beat && currentSubdivision === 0,
                                'is-accent': item.sound === 'accent',
                                'is-click': item.sound === 'click',
                                'is-rest': item.sound === 'rest'
                            }"
                        >
                            <span
                                x-text="
                                    item.sound === 'accent'
                                        ? 'A'
                                        : item.sound === 'click'
                                            ? 'C'
                                            : item.sound === 'rest'
                                                ? 'R'
                                                : '-'
                                "
                            ></span>

                            <small x-text="item.beat"></small>
                        </button>

                        <template
                            x-for="(subdivisionItem, subdivisionIndex) in (item.subdivisions ?? [])"
                            :key="`${item.beat}-${subdivisionIndex}`"
                        >
                            <button
                                class="subdivision-mark"
                                type="button"
                                @if (! $interactive)
                                    aria-disabled="true"
                                @endif
                                @if ($interactive)
                                    @click="applyEditorToolToSubdivision(
                                        item.beat,
                                        subdivisionIndex
                                    )"
                                @endif
                                :class="{
                                    'is-active': currentBeat === item.beat && currentSubdivision === subdivisionIndex + 1,
                                    'is-accent': subdivisionItem.sound === 'accent',
                                    'is-click': subdivisionItem.sound === 'click',
                                    'is-rest': subdivisionItem.sound === 'rest'
                                }"
                            >
                                <span
                                    x-text="
                                        subdivisionItem.sound === 'accent'
                                            ? 'A'
                                            : subdivisionItem.sound === 'click'
                                                ? 'C'
                                                : 'R'
                                    "
                                ></span>

                                <small x-text="subdivisionItem.label"></small>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
