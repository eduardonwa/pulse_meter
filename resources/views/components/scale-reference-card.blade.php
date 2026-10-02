<section
    class="scale-reference-card"
    x-data="{ scaleLabelMode: 'degrees' }"
    x-show="randomizerResult"
    x-cloak
>
    <div class="scale-reference-card__header">
        <div>
            <h2
                class="scale-reference-card__title"
                x-text="getRandomizerScaleInfo()?.title ?? ''"
            ></h2>

            <p
                class="scale-reference-card__description"
                x-text="getRandomizerScaleInfo()?.description ?? ''"
            ></p>
        </div>

        <div
            class="scale-reference-card__switch"
            role="group"
            aria-label="Scale labels"
        >
            <button
                type="button"
                class="scale-reference-card__switch-button"
                :class="{ 'is-active': scaleLabelMode === 'degrees' }"
                @click="scaleLabelMode = 'degrees'"
                :aria-pressed="scaleLabelMode === 'degrees'"
            >
                Degrees
            </button>

            <button
                type="button"
                class="scale-reference-card__switch-button"
                :class="{ 'is-active': scaleLabelMode === 'formula' }"
                @click="scaleLabelMode = 'formula'"
                :aria-pressed="scaleLabelMode === 'formula'"
            >
                Formula
            </button>
        </div>
    </div>

    <div
        class="scale-reference-card__notes"
        role="list"
        aria-label="Scale notes"
    >
        <template
            x-for="(note, index) in (getRandomizerScaleInfo()?.notes ?? [])"
            :key="`${note}-${index}`"
        >
            <div class="scale-reference-card__note" role="listitem">
                <span
                    class="scale-reference-card__note-name"
                    x-text="note"
                ></span>

                <span
                    class="scale-reference-card__note-label"
                    x-text="
                        scaleLabelMode === 'degrees'
                            ? getRandomizerScaleInfo().degrees[index]
                            : getRandomizerScaleInfo().formula[index]
                    "
                ></span>
            </div>
        </template>
    </div>
</section>

@once
    <style>
        [x-cloak] { display: none !important; }

        .scale-reference-card {
            width: 100%;
            margin-block: 1rem;
            padding: 1rem;
            border: 1px solid #cfcfcf;
            border-radius: .6rem;
            background: #fff;
            color: #1b1b1b;
        }

        .scale-reference-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .scale-reference-card__title {
            margin: 0;
            font-size: 1.1rem;
            line-height: 1.25;
        }

        .scale-reference-card__description {
            max-width: 50rem;
            margin: .45rem 0 0;
            color: #626262;
            font-size: .9rem;
            line-height: 1.5;
        }

        .scale-reference-card__switch {
            display: inline-flex;
            flex-shrink: 0;
            padding: .2rem;
            border: 1px solid #cfcfcf;
            border-radius: .45rem;
            background: #f5f5f5;
        }

        .scale-reference-card__switch-button {
            appearance: none;
            border: 0;
            border-radius: .3rem;
            padding: .4rem .65rem;
            background: transparent;
            color: #626262;
            font: inherit;
            font-size: .8rem;
            cursor: pointer;
        }

        .scale-reference-card__switch-button.is-active {
            background: #171717;
            color: #fff;
        }

        .scale-reference-card__notes {
            display: grid;
            grid-template-columns: repeat(8, minmax(3.4rem, 1fr));
            gap: .65rem;
            overflow-x: auto;
            padding-bottom: .25rem;
        }

        .scale-reference-card__note {
            min-width: 3.4rem;
            text-align: center;
        }

        .scale-reference-card__note-name {
            display: grid;
            place-items: center;
            width: 3rem;
            height: 3rem;
            margin-inline: auto;
            border: 1px solid #bdbdbd;
            border-radius: 50%;
            background: #f8f8f8;
            font-weight: 700;
        }

        .scale-reference-card__note:first-child .scale-reference-card__note-name,
        .scale-reference-card__note:last-child .scale-reference-card__note-name {
            border-color: #1760a8;
            background: #edf5ff;
        }

        .scale-reference-card__note-label {
            display: block;
            margin-top: .55rem;
            color: #5d5d5d;
            font-size: .8rem;
            font-weight: 700;
        }

        @media (max-width: 700px) {
            .scale-reference-card__header {
                flex-direction: column;
            }
        }
    </style>
@endonce
