import assert from 'node:assert/strict'
import { readFileSync, existsSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const root = resolve(import.meta.dirname, '../..')
const read = path => readFileSync(resolve(root, path), 'utf8')

test('Laravel keeps a single application entry and marks practice pages', () => {
    const head = read('resources/views/components/head.blade.php')
    const app = read('resources/js/app.js')
    assert.match(head, /@vite\(\[\s*'resources\/styles\/main\.scss',\s*'resources\/js\/app\.js'\s*\]\)/)
    for (const route of ['welcome', 'routines.show', 'shared.show']) {
        assert.ok(head.includes(route), `Practice route not marked: ${route}`)
    }
    assert.match(head, /name="dorelog-practice"/)
    assert.match(app, /needsPractice:\s*Boolean\(document\.querySelector/)
    assert.match(app, /await Promise\.all\(/)
    assert.match(app, /import\(['"]\.\/metronome['"]\)/)
    assert.match(app, /import\(['"]\.\/alphatab-exercises['"]\)/)
    assert.match(app, /startLivewire:\s*\(\)\s*=>\s*Livewire\.start\(\)/)
})

test('production build contains practice factories in awaited lazy chunks', () => {
    const manifestPath = resolve(root, 'public/build/manifest.json')
    assert.ok(existsSync(manifestPath), 'Missing Vite manifest: run npm run build')
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'))
    const entry = manifest['resources/js/app.js']
    assert.ok(entry?.isEntry, 'App entry is missing from Vite manifest')

    const seen = new Set()
    const staticCode = key => {
        if (seen.has(key)) return ''
        seen.add(key)
        const chunk = manifest[key]
        assert.ok(chunk?.file, `Missing manifest chunk: ${key}`)
        const file = resolve(root, 'public/build', chunk.file)
        assert.ok(existsSync(file), `Missing output file: ${chunk.file}`)
        return (chunk.file.endsWith('.js') ? readFileSync(file, 'utf8') : '')
            + (chunk.imports ?? []).map(staticCode).join('\n')
    }

    const entryCode = staticCode('resources/js/app.js')
    const dynamic = entry.dynamicImports ?? []
    const practiceChunks = [
        'resources/js/metronome/index.js',
        'resources/js/alphatab-exercises.js',
    ]
    for (const key of practiceChunks) {
        assert.ok(dynamic.includes(key), `Practice module not a lazy dependency: ${key}`)
    }

    const practiceCode = practiceChunks.map(key => {
        seen.clear()
        return staticCode(key)
    }).join('\n')

    for (const name of ['routinePlayer', 'routineTemplateGuest', 'numberPicker', 'alphaTabExerciseControls']) {
        assert.match(practiceCode, new RegExp(`(?:window|globalThis)\\.${name}\\s*=`),
            `Missing practice factory: ${name}`)
    }
    // This cannot prove runtime ordering; tests/JavaScript/bootstrap.test.js
    // exercises the await before the Livewire startup callback.
    assert.ok(entryCode, 'App entry output should not be empty')
})
