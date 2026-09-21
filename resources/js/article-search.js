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
        requestLoading: false,
        requestSent: false,
        requestError: '',
        request: {
            name: '',
            email: '',
            website: '',
        },
        ...config,

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

            this.query = ''
            this.loading = true
            this.unansweredQuestion = ''
            this.requestSent = false
            this.requestError = ''

            try {
                const response = await requestClient(this.searchUrl, {
                    query: question,
                })

                if (response.matched) {
                    this.messages.push({
                        author: 'assistant',
                        type: 'result',
                        article: response.article,
                    })

                    return
                }

                this.messages.push({
                    author: 'assistant',
                    type: 'text',
                    text: this.strings.empty,
                })
                this.unansweredQuestion = question
            } catch (error) {
                this.messages.push({
                    author: 'assistant',
                    type: 'text',
                    text: this.strings.error,
                })
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
