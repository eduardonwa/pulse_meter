import test from 'node:test'
import assert from 'node:assert/strict'

import { articleSearch } from '../../resources/js/article-search.js'

const translations = {
    es: {
        empty: 'Todavía no hay respuesta.',
        error: 'Falló la búsqueda.',
        request_error: 'Falló la solicitud.',
    },
    en: {
        empty: 'No answer yet.',
        error: 'Search failed.',
        request_error: 'Request failed.',
    },
}

function config(locale = 'en') {
    return {
        searchUrl: '/chat/search',
        questionUrl: '/chat/questions',
        locale,
        translations,
    }
}

test('chat adds the best result to the conversation', async () => {
    const article = {
        title: 'Practice with a metronome',
        excerpt: 'Choose a useful tempo.',
        url: '/en/blog/practice-with-a-metronome',
    }

    const state = articleSearch(
        config(),
        async (url, body) => {
            assert.equal(url, '/chat/search')
            assert.deepEqual(body, {
                query: 'How should I use a metronome?',
                locale: 'en',
            })

            return {
                matched: true,
                locale: 'en',
                resource: { ...article, type: 'article' },
            }
        },
    )

    state.query = 'How should I use a metronome?'
    await state.search()

    assert.equal(state.messages.length, 2)
    assert.equal(state.messages[0].author, 'user')
    assert.equal(state.messages[1].type, 'result')
    assert.deepEqual(state.messages[1].resource, {
        ...article,
        type: 'article',
    })
    assert.equal(state.unansweredQuestion, '')
    assert.equal(state.loading, false)
})

test('chat switches its conversation language to the detected language', async () => {
    const state = articleSearch(
        config('es'),
        async () => ({ matched: false, locale: 'en' }),
    )

    state.query = 'What should I practice today?'
    await state.search()

    assert.equal(state.conversationLocale, 'en')
    assert.equal(state.messages[1].text, translations.en.empty)
})

test('an unanswered search opens the personal response request', async () => {
    const state = articleSearch(
        config('es'),
        async () => ({ matched: false, locale: 'es' }),
    )

    state.query = '¿Qué pedal debería comprar?'
    await state.search()

    assert.equal(state.messages[1].text, translations.es.empty)
    assert.equal(
        state.unansweredQuestion,
        '¿Qué pedal debería comprar?',
    )
})

test('a reader can submit an unanswered question with their email and language', async () => {
    let submission

    const state = articleSearch(
        config('es'),
        async (url, body) => {
            submission = { url, body }

            return { submitted: true }
        },
    )

    state.unansweredQuestion = '¿Cómo practico gallops?'
    state.unansweredLocale = 'es'
    state.request.name = 'Ana'
    state.request.email = 'ana@example.com'

    await state.submitQuestion()

    assert.equal(submission.url, '/chat/questions')
    assert.equal(submission.body.question, '¿Cómo practico gallops?')
    assert.equal(submission.body.locale, 'es')
    assert.equal(submission.body.email, 'ana@example.com')
    assert.equal(state.requestSent, true)
    assert.equal(state.requestLoading, false)
})
