// Keep the active beat visible without scrolling the surrounding page.
export function scrollActiveBeatIntoView(strip, currentBeat) {
    if (!strip || !Number.isInteger(Number(currentBeat))) return

    const index = Number(currentBeat) - 1
    const beat = strip.children?.[index]
    if (!beat) return

    const viewportLeft = strip.scrollLeft
    const viewportRight = viewportLeft + strip.clientWidth
    const beatLeft = beat.offsetLeft - strip.offsetLeft
    const beatRight = beatLeft + beat.offsetWidth

    if (beatLeft < viewportLeft) {
        strip.scrollLeft = beatLeft
    } else if (beatRight > viewportRight) {
        strip.scrollLeft = beatRight - strip.clientWidth
    }
}
