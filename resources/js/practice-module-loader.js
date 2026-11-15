// Optional UI/audio chunks must not prevent Alpine from binding routine controls.
// The metronome factories are required for routinePlayer() and exercise panels.
export async function loadPracticeModules({
    loadMetronome,
    loadAlphaTab,
    loadOnboarding = null,
    onOptionalFailure = (moduleName, error) => console.error(`Dorelog ${moduleName} failed to load:`, error),
}) {
    const optional = [['AlphaTab', loadAlphaTab]]
    if (loadOnboarding) optional.push(['onboarding', loadOnboarding])

    // Start downloading optional chunks in parallel, but never let their failures
    // stop Livewire/Alpine from binding the page's Start/View controls.
    const pendingOptional = optional.map(async ([name, load]) => {
        try {
            await load()
        } catch (error) {
            onOptionalFailure(name, error)
        }
    })

    let metronomeError = null
    try {
        await loadMetronome()
    } catch (error) {
        metronomeError = error
    }

    await Promise.all(pendingOptional)
    if (metronomeError) throw metronomeError
}
