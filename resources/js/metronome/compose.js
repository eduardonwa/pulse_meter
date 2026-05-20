export function compose(...parts) {
    return parts.reduce((component, part) => {
        return Object.defineProperties(
            component,
            Object.getOwnPropertyDescriptors(part)
        )
    }, {})
}