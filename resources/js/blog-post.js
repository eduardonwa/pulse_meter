function getHashTarget(hash = window.location.hash) {
    if (!hash) return null

    try {
        return document.querySelector(hash)
    } catch {
        return null
    }
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
    const link = event.target.closest('.table-of-contents a[href^="#"]')

    if (!link) return

    event.preventDefault()

    const hash = link.hash

    history.pushState(null, '', hash)
    alignHash(hash, 'smooth')
})