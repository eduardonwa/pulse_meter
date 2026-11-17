import test from 'node:test'
import assert from 'node:assert/strict'
import { exercises } from '../../resources/js/metronome/exercises.js'

test('a full MusicXML score is playable without being converted to editable beats', () => {
    const e = exercises()
    const source = '<score-partwise><part><measure number="1"><note><notations><technical><tap>1</tap></technical></notations></note></measure></part></score-partwise>'
    e.tabMusicXmlFullSource = source
    e.tabMusicXmlFileName = 'Picking Stamina'
    e.tabEditorEditingIndex = null
    e.stopTabEditorPreview = () => {}
    e.recordTabEditorUndo = () => {}
    e.applyTabMusicXmlFullScore()

    assert.equal(e.tabFullMusicXml, source)
    assert.deepEqual(e.tabDraft.beats, [])
    assert.equal(e.tabMusicXmlImport, null)
    assert.equal(e.tabMusicXmlFullSource, null)
    assert.equal(e.stepForm.name, 'Picking Stamina')
    assert.ok(e.hasAlphaTabPattern({ alpha_tex: null, musicxml_source: source }))
    assert.ok(e.hasAlphaTabPattern({ alpha_tex: ':4 0.1', musicxml_source: null }))
    assert.equal(e.hasAlphaTabPattern({ alpha_tex: null, musicxml_source: null }), false)
})
