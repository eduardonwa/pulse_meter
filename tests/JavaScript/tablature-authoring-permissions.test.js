import assert from 'node:assert/strict'
import test from 'node:test'

import { state } from '../../resources/js/metronome/state.js'
import { exercises } from '../../resources/js/metronome/exercises.js'

test('guest and free contexts cannot author tablatures', () => {
    assert.equal(state({ persistence: 'local' }).canCreateTablatures, false)
    assert.equal(state({ persistence: 'local', capabilities: { tablature_creation: false } }).canCreateTablatures, false)
    assert.equal(state({ persistence: 'local', capabilities: { tablature_creation: true } }).canCreateTablatures, true)
})

test('published and shared template playback never grants authoring', () => {
    const session = state({
        mode: 'routine',
        persistence: 'template',
        capabilities: { tablature_creation: true },
    })
    assert.equal(session.canCreateTablatures, false)
})

test('tab editor cannot open or save without authoring capability', async () => {
    const session = exercises()
    session.canCreateTablatures = false
    session.stepFormMode = 'create'
    session.steps = []
    session.maxSteps = 10
    session.$refs = {
        stepDialog: { close() { throw Error('Editor should not open') } },
    }
    session.$nextTick = () => {
        throw Error('Editor should not initialize')
    }

    session.openNewTabEditor()
    session.openSavedTabEditor(0)
    await session.saveNewTabExercise()

    assert.equal(session.tabEditorOpen, false)
    assert.deepEqual(session.steps, [])
})

test('the authoring state is enabled for an authorized routine', () => {
    assert.equal(state({
        mode: 'routine',
        persistence: 'server',
        capabilities: { tablature_creation: true },
    }).canCreateTablatures, true)
})
