const debounceTimers = new Map();

function createUuid() {
    if (
        typeof globalThis.crypto?.randomUUID === 'function'
    ) {
        return globalThis.crypto.randomUUID();
    }

    if (
        typeof globalThis.crypto?.getRandomValues === 'function'
    ) {
        const bytes = new Uint8Array(16);

        globalThis.crypto.getRandomValues(bytes);

        // UUID versión 4.
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;

        const hex = Array.from(
            bytes,
            byte => byte.toString(16).padStart(2, '0')
        );

        return [
            hex.slice(0, 4).join(''),
            hex.slice(4, 6).join(''),
            hex.slice(6, 8).join(''),
            hex.slice(8, 10).join(''),
            hex.slice(10, 16).join(''),
        ].join('-');
    }

    // Último respaldo. Es suficiente para IDs anónimos
    // de analytics, pero no para seguridad o autenticación.
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'
        .replace(/[xy]/g, character => {
            const random = Math.floor(Math.random() * 16);

            const value = character === 'x'
                ? random
                : (random & 0x3) | 0x8;

            return value.toString(16);
        });
}

function getStoredUuid(storage, key, fallback) {
    try {
        let value = storage.getItem(key);

        if (!value) {
            value = createUuid();
            storage.setItem(key, value);
        }

        return value;
    } catch {
        return fallback;
    }
}

const fallbackVisitorId = createUuid();
const fallbackSessionId = createUuid();

function getVisitorId() {
    return getStoredUuid(
        localStorage,
        'dorelog:visitor-id',
        fallbackVisitorId
    );
}

function getSessionId() {
    return getStoredUuid(
        sessionStorage,
        'dorelog:session-id',
        fallbackSessionId
    );
}

function getMetaContent(name) {
    return document
        .querySelector(`meta[name="${name}"]`)
        ?.getAttribute('content');
}

export function trackProductEvent(
    eventName,
    properties = {}
) {
    const endpoint = getMetaContent(
        'product-events-endpoint'
    );

    const csrfToken = getMetaContent('csrf-token');

    if (!endpoint || !csrfToken) {
        return Promise.resolve(null);
    }

    const payload = {
        event_id: createUuid(),
        visitor_id: getVisitorId(),
        session_id: getSessionId(),
        event_name: eventName,
        properties,
        path: window.location.pathname,
    };

    return fetch(endpoint, {
        method: 'POST',

        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },

        credentials: 'same-origin',

        // Ayuda a que una petición corta termine
        // aunque el usuario esté cerrando la página.
        keepalive: true,

        body: JSON.stringify(payload),
    }).catch(() => {
        // Nunca dejamos que analytics rompa el metrónomo.
        return null;
    });
}

export function trackProductEventDebounced(
    debounceKey,
    eventName,
    properties,
    delay = 700
) {
    const existingTimer = debounceTimers.get(debounceKey);

    if (existingTimer) {
        clearTimeout(existingTimer);
    }

    const timer = setTimeout(() => {
        trackProductEvent(eventName, properties);
        debounceTimers.delete(debounceKey);
    }, delay);

    debounceTimers.set(debounceKey, timer);
}

export function initializeProductAnalytics() {
    const flag = 'dorelog:app-opened';

    try {
        if (sessionStorage.getItem(flag)) {
            return;
        }

        sessionStorage.setItem(flag, '1');
    } catch {
        // Continuamos aunque sessionStorage esté bloqueado.
    }

    trackProductEvent('app_opened');
}