// Reveal the active beat immediately, keeping the page and fixed BPM in place.
export function scrollActiveBeatIntoView(strip, currentBeat) {
    if (!strip || !Number.isInteger(Number(currentBeat))) return

    const beat = strip.children?.[Number(currentBeat) - 1]
    if (!beat || !strip.clientWidth) return

    // Rectangles are relative to the viewport, even when offset parents differ.
    const stripRect = strip.getBoundingClientRect?.()
    const beatRect = beat.getBoundingClientRect?.()

    if (!stripRect || !beatRect) return

    const inset = 4
    if (beatRect.right > stripRect.right - inset) {
        strip.scrollLeft += beatRect.right - stripRect.right + inset
    } else if (beatRect.left < stripRect.left + inset) {
        strip.scrollLeft -= stripRect.left + inset - beatRect.left
    }
}
