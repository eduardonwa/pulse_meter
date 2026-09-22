function csrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? ''
}

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    })

    if (! response.ok) {
        throw new Error(`Request failed with status ${response.status}`)
    }

    return response.json()
}

export function articleSearch(config, requestClient = postJson) {
    return {
        query: '',
        loading: false,
        messages: [],
        unansweredQuestion: '',
        unansweredLocale: config.locale,
        requestLoading: false,
        requestSent: false,
        requestError: '',
        request: {
            name: '',
            email: '',
            website: '',
        },
        ...config,

        conversationLocale: config.locale,

        get strings() {
            return this.translations?.[this.conversationLocale]
                ?? config.strings
                ?? {}
        },

        scrollToLatest() {
            if (typeof this.$nextTick !== 'function') {
                return
            }

            this.$nextTick(() => {
                const conversation = this.$refs?.conversation

                conversation?.scrollTo({
                    top: conversation.scrollHeight,
                    behavior: 'smooth',
                })
            })
        },

        async search() {
            const question = this.query.trim()

            if (question.length < 3 || this.loading) {
                return
            }

            this.messages.push({
                author: 'user',
                type: 'text',
                text: question,
            })
            this.scrollToLatest()

            this.query = ''
            this.loading = true
            this.unansweredQuestion = ''
            this.requestSent = false
            this.requestError = ''

            try {
                const response = await requestClient(this.searchUrl, {
                    query: question,
                    locale: this.conversationLocale,
                })

                this.unansweredLocale = response.locale
                    ?? this.conversationLocale
                this.conversationLocale = this.unansweredLocale

                if (response.matched) {
                    this.messages.push({
                        author: 'assistant',
                        type: 'result',
                        resource: response.resource,
                    })
                    this.scrollToLatest()

                    return
                }

                this.messages.push({
                    author: 'assistant',
                    type: 'text',
                    text: this.strings.empty,
                })
                this.unansweredQuestion = question
                this.scrollToLatest()
            } catch (error) {
                this.messages.push({
                    author: 'assistant',
                    type: 'text',
                    text: this.strings.error,
                })
                this.scrollToLatest()
            } finally {
                this.loading = false
            }
        },

        async submitQuestion() {
            if (
                ! this.unansweredQuestion
                || ! this.request.email
                || this.requestLoading
            ) {
                return
            }

            this.requestLoading = true
            this.requestError = ''

            try {
                await requestClient(this.questionUrl, {
                    question: this.unansweredQuestion,
                    locale: this.unansweredLocale,
                    ...this.request,
                })

                this.requestSent = true
            } catch (error) {
                this.requestError = this.strings.request_error
            } finally {
                this.requestLoading = false
            }
        },
    }
}

export function registerArticleSearch(Alpine) {
    Alpine.data('articleSearch', articleSearch)
}
