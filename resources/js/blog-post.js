function getHashTarget(hash) {
    if (!hash) return

    return document.querySelector(hash)
}

function alignHash(hash, behavior = 'auto') {
    getHashTarget(hash)?.scrollIntoView({
        block: 'start',
        behavior,
    })
}

window.addEventListener('load', () => {
    alignHash(window.location.hash)
})

document.addEventListener('click', event => {
    const link = event.target.closest(
        '.table-of-contents a[href^="#"]'
    )

    if (!link) return

    event.preventDefault()

    const hash = link.hash

    history.pushState(null, '', hash)
    alignHash(hash, 'smooth')
})