<x-layouts.dorelog
    title="{{ __('chat.meta_title') }}"
    description="{{ __('chat.meta_description') }}"
>
    <main
        class="content-chat"
        x-data="articleSearch({
            searchUrl: @js(route('chat.search')),
            questionUrl: @js(route('chat.questions.store')),
            locale: @js($locale),
            translations: {
                es: @js(trans('chat', [], 'es')),
                en: @js(trans('chat', [], 'en')),
            },
        })"
        x-init="$nextTick(() => $refs.query.focus())"
    >
        <section class="content-chat__shell">
            <header class="content-chat__header">
                <p class="content-chat__eyebrow" x-text="strings.eyebrow"></p>
                <h1 class="heading-2" x-text="strings.heading"></h1>
                <p x-text="strings.description"></p>
            </header>

            <div
                class="content-chat__conversation"
                x-ref="conversation"
                role="log"
                aria-live="polite"
                aria-relevant="additions"
            >
                <template x-for="(message, index) in messages" :key="index">
                    <div
                        class="content-chat__message"
                        :data-author="message.author"
                    >
                        <template x-if="message.type === 'text'">
                            <p x-text="message.text"></p>
                        </template>

                        <template x-if="message.type === 'result'">
                            <div>
                                <p
                                    x-text="message.resource.type === 'routine'
                                        ? strings.routine_intro
                                        : strings.result_intro"
                                ></p>

                                <a
                                    class="content-chat__result"
                                    :href="message.resource.url"
                                >
                                    <strong x-text="message.resource.title"></strong>
                                    <span x-text="message.resource.excerpt"></span>
                                    <span class="content-chat__result-link">
                                        <span
                                            x-text="message.resource.type === 'routine'
                                                ? strings.open_routine
                                                : strings.read_article"
                                        ></span>
                                        <span aria-hidden="true">→</span>
                                    </span>
                                </a>
                            </div>
                        </template>
                    </div>
                </template>

                <div
                    class="content-chat__message"
                    data-author="assistant"
                    x-show="loading"
                    x-cloak
                >
                    <p x-text="strings.loading"></p>
                </div>

                <form
                    class="content-chat__request"
                    x-show="unansweredQuestion && ! requestSent"
                    x-cloak
                    @submit.prevent="submitQuestion"
                >
                    <div>
                        <h2 class="heading-3" x-text="strings.request_heading"></h2>
                        <p x-text="strings.request_intro"></p>
                    </div>

                    <label>
                        <span x-text="strings.name"></span>
                        <input
                            type="text"
                            x-model="request.name"
                            maxlength="100"
                            autocomplete="name"
                        >
                    </label>

                    <label>
                        <span x-text="strings.email"></span>
                        <input
                            type="email"
                            x-model="request.email"
                            maxlength="255"
                            autocomplete="email"
                            required
                        >
                    </label>

                    <div class="content-chat__honeypot" aria-hidden="true">
                        <label>
                            Website
                            <input
                                type="text"
                                x-model="request.website"
                                tabindex="-1"
                                autocomplete="off"
                            >
                        </label>
                    </div>

                    <p class="content-chat__privacy" x-text="strings.privacy"></p>

                    <p
                        class="content-chat__error"
                        x-show="requestError"
                        x-text="requestError"
                        x-cloak
                    ></p>

                    <button
                        class="button content-chat__request-submit"
                        type="submit"
                        :disabled="requestLoading"
                    >
                        <span
                            x-text="requestLoading
                                ? strings.request_sending
                                : strings.request_submit"
                        ></span>
                    </button>
                </form>

                <div
                    class="content-chat__success"
                    x-show="requestSent"
                    x-cloak
                    role="status"
                >
                    <p x-text="strings.request_success"></p>
                </div>
            </div>

            <form class="content-chat__composer" @submit.prevent="search">
                <label class="sr-only" for="content-chat-query" x-text="strings.heading"></label>

                <textarea
                    id="content-chat-query"
                    x-ref="query"
                    x-model="query"
                    :disabled="loading"
                    rows="2"
                    maxlength="500"
                    required
                    :placeholder="strings.placeholder"
                    @keydown.enter.exact.prevent="search"
                ></textarea>

                <button
                    class="button content-chat__send"
                    type="submit"
                    :disabled="loading || query.trim().length < 3"
                >
                    <x-heroicon-o-paper-airplane aria-hidden="true" />
                    <span class="sr-only" x-text="strings.submit"></span>
                </button>
            </form>
        </section>
    </main>
</x-layouts.dorelog>
