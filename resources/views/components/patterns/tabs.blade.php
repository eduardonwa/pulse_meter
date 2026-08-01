<div class="pattern-tabs">
    <template
        x-for="collection in getPulsePresetCollections()"
        :key="collection"
    >
        <button class="button" type="button" data-type="pattern-tab"
            @click="selectPatternTab(collection)"
            :class="{
                'is-selected': activePatternTab === collection
            }"
            x-text="getPulseCollectionLabel(collection)"
        ></button>
    </template>

    <button class="button" type="button" data-type="pattern-tab"
        x-show="userPatterns.length"
        @click="selectPatternTab('user')"
        :class="{
            'is-selected': activePatternTab === 'user'
        }"
    >
        Custom
    </button>
</div>