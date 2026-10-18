@props([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

<section class="metronome__panel">
    @if (request()->boolean('audio_debug'))
        {{-- Temporary on-device diagnostics; opt-in via ?audio_debug=1. --}}
        <details style="position:fixed;bottom:0;left:0;right:0;z-index:10000;background:#fff;color:#111;border:2px solid #222;padding:8px;max-height:65dvh;overflow:auto;font:12px/1.4 monospace;">
            <summary style="cursor:pointer;font-weight:bold;padding:8px;">Dorelog Audio Debug</summary>
            <p>State: <strong x-text="audioContext?.state ?? 'missing'"></strong> | Click: <span x-text="clickBuffer ? 'loaded' : 'missing'"></span> | Accent: <span x-text="accentBuffer ? 'loaded' : 'missing'"></span></p>
            <p>Reproduce the problem, then tap Copy Debug. If clipboard access is blocked on local HTTP, tap the text field and select/copy manually.</p>
            <button type="button" style="padding:8px;border:1px solid #555;margin-bottom:8px;margin-right:8px;"
                @click="testAudioOutput()">Test Audio (tone)</button>
            <button type="button" style="padding:8px;border:1px solid #555;margin-bottom:8px;"
                @click="(async () => { const data = JSON.stringify(audioEvents, null, 2); try { await navigator.clipboard.writeText(data); $el.textContent = 'Copied!'; } catch (error) { $refs.audioDebugText.focus(); $refs.audioDebugText.select(); $el.textContent = 'Select and copy below'; } })()">Copy Debug</button>
            <textarea x-ref="audioDebugText" readonly style="width:100%;min-height:190px;border:1px solid #777;padding:8px;font:11px monospace;"
                :value="JSON.stringify(audioEvents, null, 2)"
                @focus="$el.select()"></textarea>
        </details>
    @endif

    <x-metronome.panel-tabs />

    <x-metronome.panel-sessions />

    <x-metronome.panel-exercises />

    <x-metronome.panel-settings />
</section>