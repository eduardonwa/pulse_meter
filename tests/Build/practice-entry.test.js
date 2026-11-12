import assert from 'node:assert/strict'
import { readFileSync, existsSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const root = resolve(import.meta.dirname, '../..')
const read = path => readFileSync(resolve(root, path), 'utf8')

test('Laravel uses a single, stable Vite entry for practice and ordinary pages', () => {
    const head = read('resources/views/components/head.blade.php')
    const app = read('resources/js/app.js')
    const welcome = read('resources/views/welcome.blade.php')

    assert.match(head, /@vite\(\[\s*'resources\/styles\/main\.scss',\s*'resources\/js\/app\.js'\s*\]\)/)
    assert.doesNotMatch(head, /practice\.js/)

    // The static imports are evaluated before the module body calls Livewire.start().
    // Keep both registrations as static dependencies of the one application entry.
    assert.match(app, /import\s+['"]\.\/metronome['"]/)
    assert.match(app, /import\s+['"]\.\/alphatab-exercises['"]/)
    assert.match(app, /Livewire\.start\(\)/)
    assert.match(welcome, /x-data="routinePlayer\(/)
})

test('production Vite entry statically reaches practice Alpine registrations', () => {
    const manifestPath = resolve(root, 'public/build/manifest.json')
    assert.ok(existsSync(manifestPath), 'Missing public/build/manifest.json — run npm run build first')

    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'))
    const entry = manifest['resources/js/app.js']
    assert.ok(entry?.isEntry, 'The Laravel app.js entry is missing from the Vite manifest')

    const visited = new Set()
    const javascript = []
    const visit = key => {
        if (visited.has(key)) return
        visited.add(key)
        const chunk = manifest[key]
        assert.ok(chunk, `Missing Vite chunk: ${key}`)
        assert.ok(chunk.file, `Missing output file for chunk: ${key}`)
        const path = resolve(root, 'public/build', chunk.file)
        assert.ok(existsSync(path), `Missing built asset: ${chunk.file}`)
        if (chunk.file.endsWith('.js')) javascript.push(readFileSync(path, 'utf8'))
        for (const imported of chunk.imports ?? []) visit(imported)
        // Do not traverse dynamicImports: those are NOT guaranteed to finish
        // before Livewire initializes Alpine.
    }
    visit('resources/js/app.js')

    const bundled = javascript.join('\n')
    for (const name of ['routinePlayer', 'routineTemplateGuest', 'numberPicker', 'alphaTabExerciseControls']) {
        assert.match(
            bundled,
            new RegExp(`(?:window|globalThis)\\.${name}\\s*=`),
            `The production app entry does not statically register ${name} before Alpine starts`,
        )
    }
})
