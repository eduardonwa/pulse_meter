<x-layouts.dorelog title="{{ __('blog.title') }}" description="{{ __('blog.description') }}">
    <section class="container | posts" data-type="blog-post">
        <div class="flow">
            <div class="lang" x-data="{ dropdownOpen: false }">

                <button type="button" class="button" data-type="icon-text"
                    @click="dropdownOpen = ! dropdownOpen"
                    @keydown.escape.window="dropdownOpen = false"
                    @click.outside="dropdownOpen = false"
                >
                    <x-heroicon-o-language />
                    Language:
                    {{ app()->getLocale() === 'es' ? 'Español' : 'English' }}
                    <x-heroicon-m-chevron-down />
                </button>

                <div class="lang__options" x-show="dropdownOpen === true" x-cloak>
                    <a class="option" href="{{ route('blog.index', ['locale' => 'en']) }}">
                        English
                    </a>
    
                    <a class="option" href="{{ route('blog.index', ['locale' => 'es']) }}">
                        Español
                    </a>
                </div>
            </div>

            <h1 class="heading-3">Dorelog Blog</h1>
            <div class="fs-300">
                {{ __('blog.subheader') }} <a href="">{{ __('blog.contact_link') }}</a>
            </div>
        </div>

        <section
            class="article-search"
            x-data="articleSearch({
                searchUrl: @js(route('blog.search', ['locale' => app()->getLocale()])),
                questionUrl: @js(route('blog.questions.store', ['locale' => app()->getLocale()])),
                strings: @js(__('blog.search')),
            })"
        >
            <div class="article-search__intro">
                <p class="article-search__eyebrow">{{ __('blog.search.eyebrow') }}</p>
                <h2 class="heading-3">{{ __('blog.search.heading') }}</h2>
                <p>{{ __('blog.search.description') }}</p>
            </div>

            <div
                class="article-search__conversation"
                role="log"
                aria-live="polite"
                aria-relevant="additions"
            >
                <template x-for="(message, index) in messages" :key="index">
                    <div
                        class="article-search__message"
                        :data-author="message.author"
                    >
                        <template x-if="message.type === 'text'">
                            <p x-text="message.text"></p>
                        </template>

                        <template x-if="message.type === 'result'">
                            <div>
                                <p x-text="strings.result_intro"></p>

                                <a
                                    class="article-search__result"
                                    :href="message.article.url"
                                >
                                    <strong x-text="message.article.title"></strong>
                                    <span x-text="message.article.excerpt"></span>
                                    <span class="article-search__result-link">
                                        <span x-text="strings.read_article"></span>
                                        <span aria-hidden="true">→</span>
                                    </span>
                                </a>
                            </div>
                        </template>
                    </div>
                </template>

                <div
                    class="article-search__message"
                    data-author="assistant"
                    x-show="loading"
                    x-cloak
                >
                    <p x-text="strings.loading"></p>
                </div>
            </div>

            <form class="article-search__ask" @submit.prevent="search">
                <label class="sr-only" for="article-search-query">
                    {{ __('blog.search.heading') }}
                </label>

                <textarea
                    id="article-search-query"
                    x-model="query"
                    :disabled="loading"
                    rows="3"
                    maxlength="500"
                    required
                    placeholder="{{ __('blog.search.placeholder') }}"
                    @keydown.enter.exact.prevent="search"
                ></textarea>

                <button
                    class="button article-search__submit"
                    type="submit"
                    :disabled="loading || query.trim().length < 3"
                >
                    <span x-text="loading ? strings.loading : strings.submit"></span>
                </button>
            </form>

            <form
                class="article-search__request"
                x-show="unansweredQuestion && ! requestSent"
                x-cloak
                @submit.prevent="submitQuestion"
            >
                <div>
                    <h3>{{ __('blog.search.request_heading') }}</h3>
                    <p>{{ __('blog.search.request_intro') }}</p>
                </div>

                <label>
                    <span>{{ __('blog.search.name') }}</span>
                    <input
                        type="text"
                        x-model="request.name"
                        maxlength="100"
                        autocomplete="name"
                    >
                </label>

                <label>
                    <span>{{ __('blog.search.email') }}</span>
                    <input
                        type="email"
                        x-model="request.email"
                        maxlength="255"
                        autocomplete="email"
                        required
                    >
                </label>

                <div class="article-search__honeypot" aria-hidden="true">
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

                <p class="article-search__privacy">
                    {{ __('blog.search.privacy') }}
                </p>

                <p
                    class="article-search__error"
                    x-show="requestError"
                    x-text="requestError"
                    x-cloak
                ></p>

                <button
                    class="button article-search__submit"
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
                class="article-search__success"
                x-show="requestSent"
                x-cloak
                role="status"
            >
                <p x-text="strings.request_success"></p>
            </div>
        </section>
        
        @foreach ($posts as $post)
            <a class="posts__item" href="{{ route('blog.show', [
                'locale' => app()->getLocale(),
                'slug' => $post->slug,
            ]) }}">
                <span class="posts__pattern" aria-hidden="true"></span>
                
                <h2 class="heading-3">{{ $post->title }}</h2>

                <p>{{ $post->excerpt }}</p>
                <p>{{ $post->published_at->format('F j, Y') }}</p>
            </a>
        @endforeach
    </section>
</x-layouts.dorelog>
