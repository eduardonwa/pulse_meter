// Keep the active beat near the middle of the available strip,
// rather than waiting until it has already crossed the right edge.
export function scrollActiveBeatIntoView(strip, currentBeat) {
    const beatIndex = Number(currentBeat) - 1
    if (!strip || !Number.isInteger(beatIndex) || beatIndex < 0) return

    // A new measure must start with beat 1 visible, regardless of previous scroll.
    if (beatIndex === 0) {
        strip.scrollLeft = 0
        return
    }

    const beat = strip.children?.[beatIndex]
    if (!beat || !strip.clientWidth) return

    const stripRect = strip.getBoundingClientRect?.()
    const beatRect = beat.getBoundingClientRect?.()
    if (!stripRect || !beatRect) return

    // Reading viewport coordinates works even when the beat and the strip
    // have different offset parents. The browser clamps scrollLeft at both ends.
    const stripCenter = (stripRect.left + stripRect.right) / 2
    const beatCenter = (beatRect.left + beatRect.right) / 2
    const delta = beatCenter - stripCenter
    if (Math.abs(delta) > 0.5) {
        strip.scrollLeft += delta
    }
}
