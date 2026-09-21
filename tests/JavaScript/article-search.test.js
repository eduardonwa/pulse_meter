import test from 'node:test'
import assert from 'node:assert/strict'

import { articleSearch } from '../../resources/js/article-search.js'

const strings = {
    empty: 'No answer yet.',
    error: 'Search failed.',
    request_error: 'Request failed.',
}

test('article search adds the best result to the conversation', async () => {
    const article = {
        title: 'Practice with a metronome',
        excerpt: 'Choose a useful tempo.',
        url: '/en/blog/practice-with-a-metronome',
    }

    const state = articleSearch(
        {
            searchUrl: '/en/blog/search',
            questionUrl: '/en/blog/questions',
            strings,
        },
        async (url, body) => {
            assert.equal(url, '/en/blog/search')
            assert.deepEqual(body, { query: 'How should I use a metronome?' })

            return { matched: true, article }
        },
    )

    state.query = 'How should I use a metronome?'
    await state.search()

    assert.equal(state.messages.length, 2)
    assert.equal(state.messages[0].author, 'user')
    assert.equal(state.messages[1].type, 'result')
    assert.deepEqual(state.messages[1].article, article)
    assert.equal(state.unansweredQuestion, '')
    assert.equal(state.loading, false)
})

test('an unanswered search opens the personal response request', async () => {
    const state = articleSearch(
        {
            searchUrl: '/es/blog/search',
            questionUrl: '/es/blog/questions',
            strings,
        },
        async () => ({ matched: false }),
    )

    state.query = '¿Qué pedal debería comprar?'
    await state.search()

    assert.equal(state.messages[1].text, strings.empty)
    assert.equal(
        state.unansweredQuestion,
        '¿Qué pedal debería comprar?',
    )
})

test('a reader can submit an unanswered question with their email', async () => {
    let submission

    const state = articleSearch(
        {
            searchUrl: '/es/blog/search',
            questionUrl: '/es/blog/questions',
            strings,
        },
        async (url, body) => {
            submission = { url, body }

            return { submitted: true }
        },
    )

    state.unansweredQuestion = '¿Cómo practico gallops?'
    state.request.name = 'Ana'
    state.request.email = 'ana@example.com'

    await state.submitQuestion()

    assert.equal(submission.url, '/es/blog/questions')
    assert.equal(submission.body.question, '¿Cómo practico gallops?')
    assert.equal(submission.body.email, 'ana@example.com')
    assert.equal(state.requestSent, true)
    assert.equal(state.requestLoading, false)
})
