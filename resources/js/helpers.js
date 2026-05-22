// aceptar espacios en inputs
export function isTypingInField(event) {
    const tag = event.target.tagName.toLowerCase()

    return ['input', 'textarea', 'select'].includes(tag)
}