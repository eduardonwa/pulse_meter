import assert from 'node:assert/strict'
import test from 'node:test'

import {
    storage,
} from '../../resources/js/metronome/storage.js'

function createMemoryStorage(initialValues = {}) {
    const values = new Map(
        Object.entries(initialValues)
    )

    return {
        getItem(key) {
            return values.has(key)
                ? values.get(key)
                : null
        },

        setItem(key, value) {
            values.set(key, String(value))
        },

        removeItem(key) {
            values.delete(key)
        },
    }
}

async function withBrowserGlobals(
    {
        localStorage,
        fetch,
    },
    callback
) {
    const originalLocalStorage =
        globalThis.localStorage

    const originalFetch =
        globalThis.fetch

    const originalDocument =
        globalThis.document

    globalThis.localStorage = localStorage
    globalThis.fetch = fetch

    globalThis.document = {
        querySelector(selector) {
            if (
                selector
                === 'meta[name="csrf-token"]'
            ) {
                return {
                    content: 'test-csrf-token',
                }
            }

            return null
        },
    }

    try {
        await callback()
    } finally {
        if (originalLocalStorage === undefined) {
            delete globalThis.localStorage
        } else {
            globalThis.localStorage =
                originalLocalStorage
        }

        if (originalFetch === undefined) {
            delete globalThis.fetch
        } else {
            globalThis.fetch = originalFetch
        }

        if (originalDocument === undefined) {
            delete globalThis.document
        } else {
            globalThis.document =
                originalDocument
        }
    }
}

function createStorageInstance() {
    const instance = storage()

    instance.usesServerPersistence = true

    instance.$root = {
        dataset: {
            localRoutineImportUrl:
                '/practice-routines/import-local',

            localRoutineImportMarkerKey:
                'pulse_meter_routine_import_user_10',
        },
    }

    return instance
}

function localStepsVersionA() {
    return [
        {
            id: 'local-1',
            name: 'LOCAL TEST',
            bpm: '145',
            mode: 'timer',
            duration_seconds: '90',
            origin: 'custom',
        },
        {
            id: 'local-2',
            name: 'ONLY FREE',
            bpm: 120,
            mode: 'manual',
            duration_seconds: 60,
            origin: 'custom',
        },
    ]
}

function normalizedVersionA() {
    return [
        {
            name: 'LOCAL TEST',
            bpm: 145,
            mode: 'timer',
            duration_seconds: 90,
            time_signature_numerator: 4,
            time_signature_denominator: 4,
        },
        {
            name: 'ONLY FREE',
            bpm: 120,
            mode: 'classic',
            duration_seconds: null,
            time_signature_numerator: 4,
            time_signature_denominator: 4,
        },
    ]
}

test(
    'offers local exercises when their current version has not been handled',
    async () => {
        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        localStepsVersionA()
                    ),
            })

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch: async () => {
                    throw new Error(
                        'Fetch should not be called.'
                    )
                },
            },
            async () => {
                const instance =
                    createStorageInstance()

                const pending =
                    instance
                        .getPendingLocalRoutineImport()

                const expectedSteps =
                    normalizedVersionA()

                assert.deepEqual(
                    pending,
                    {
                        type: 'first_import',

                        signature:
                            JSON.stringify(
                                expectedSteps
                            ),

                        steps:
                            expectedSteps,
                    }
                )
            }
        )
    }
)

test(
    'does not offer local exercises after that exact version was handled',
    async () => {
        const expectedSteps =
            normalizedVersionA()

        const signature =
            JSON.stringify(expectedSteps)

        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        localStepsVersionA()
                    ),

                pulse_meter_routine_import_user_10:
                    signature,
            })

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch: async () => {
                    throw new Error(
                        'Fetch should not be called.'
                    )
                },
            },
            async () => {
                const instance =
                    createStorageInstance()

                assert.equal(
                    instance
                        .getPendingLocalRoutineImport(),
                    null
                )
            }
        )
    }
)

test(
    'offers local exercises again after Free changes',
    async () => {
        const previousSignature =
            JSON.stringify(
                normalizedVersionA()
            )

        const changedLocalSteps =
            localStepsVersionA()

        changedLocalSteps[0].bpm = 175

        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        changedLocalSteps
                    ),

                pulse_meter_routine_import_user_10:
                    previousSignature,
            })

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch: async () => {
                    throw new Error(
                        'Fetch should not be called.'
                    )
                },
            },
            async () => {
                const instance =
                    createStorageInstance()

                const pending =
                    instance
                        .getPendingLocalRoutineImport()

                assert.ok(pending)

                assert.equal(
                    pending.steps[0].bpm,
                    175
                )

                assert.notEqual(
                    pending.signature,
                    previousSignature
                )
            }
        )
    }
)

test(
    'imports the explicitly accepted Free version',
    async () => {
        const savedLocalSteps =
            JSON.stringify(
                localStepsVersionA()
            )

        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    savedLocalSteps,
            })

        let requestBody = null

        const fetchMock = async (
            url,
            options
        ) => {
            requestBody =
                JSON.parse(options.body)

            return {
                ok: true,
                status: 200,

                async json() {
                    return {
                        status: 'imported',

                        routine: {
                            id: 20,
                            steps: [],
                        },
                    }
                },
            }
        }

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch:
                    fetchMock,
            },
            async () => {
                const instance =
                    createStorageInstance()

                const pending =
                    instance
                        .getPendingLocalRoutineImport()

                const result =
                    await instance
                        .importLocalRoutine(
                            pending
                        )

                assert.equal(
                    result,
                    'imported'
                )

                assert.deepEqual(
                    requestBody,
                    {
                        steps:
                            normalizedVersionA(),
                    }
                )

                /*
                 * Guardamos la firma aceptada,
                 * no solamente "imported".
                 */
                assert.equal(
                    browserStorage.getItem(
                        'pulse_meter_routine_import_user_10'
                    ),
                    pending.signature
                )

                /*
                 * El respaldo Free sigue intacto.
                 */
                assert.equal(
                    browserStorage.getItem(
                        'pulse_meter_routine'
                    ),
                    savedLocalSteps
                )
            }
        )
    }
)

test(
    'keeps server exercises by recording the current Free version',
    async () => {
        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        localStepsVersionA()
                    ),
            })

        let fetchCalls = 0

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch: async () => {
                    fetchCalls += 1

                    throw new Error(
                        'Fetch should not be called.'
                    )
                },
            },
            async () => {
                const instance =
                    createStorageInstance()

                const pending =
                    instance
                        .getPendingLocalRoutineImport()

                const result =
                    instance.keepServerRoutine(
                        pending
                    )

                assert.equal(
                    result,
                    'kept'
                )

                assert.equal(
                    fetchCalls,
                    0
                )

                assert.equal(
                    browserStorage.getItem(
                        'pulse_meter_routine_import_user_10'
                    ),
                    pending.signature
                )
            }
        )
    }
)

test(
    'does not record the Free version when Trial rejects the import',
    async () => {
        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        localStepsVersionA()
                    ),
            })

        const fetchMock = async () => {
            return {
                ok: false,
                status: 409,

                async json() {
                    return {
                        status:
                            'not_imported',

                        reason:
                            'trial_routine_limit',
                    }
                },
            }
        }

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch:
                    fetchMock,
            },
            async () => {
                const instance =
                    createStorageInstance()

                const pending =
                    instance
                        .getPendingLocalRoutineImport()

                const result =
                    await instance
                        .importLocalRoutine(
                            pending
                        )

                assert.equal(
                    result,
                    'trial_routine_limit'
                )

                /*
                 * No registramos la firma porque
                 * la importación no ocurrió.
                 */
                assert.equal(
                    browserStorage.getItem(
                        'pulse_meter_routine_import_user_10'
                    ),
                    null
                )
            }
        )
    }
)

test(
    'opens the import prompt when a Free version is pending',
    async () => {
        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        localStepsVersionA()
                    ),
            })

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch: async () => {
                    throw new Error(
                        'Fetch should not be called.'
                    )
                },
            },
            async () => {
                const instance =
                    createStorageInstance()

                const pending =
                    instance
                        .prepareLocalRoutineImport()

                assert.ok(pending)

                assert.equal(
                    instance
                        .isLocalRoutineImportOpen,
                    true
                )

                assert.deepEqual(
                    instance
                        .pendingLocalRoutineImport,
                    pending
                )

                assert.equal(
                    instance
                        .localRoutineImportError,
                    null
                )
            }
        )
    }
)

test(
    'closes the prompt after keeping the server exercises',
    async () => {
        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        localStepsVersionA()
                    ),
            })

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch: async () => {
                    throw new Error(
                        'Fetch should not be called.'
                    )
                },
            },
            async () => {
                const instance =
                    createStorageInstance()

                const pending =
                    instance
                        .prepareLocalRoutineImport()

                const result =
                    await instance
                        .resolveLocalRoutineImport(
                            'keep_server'
                        )

                assert.equal(
                    result,
                    'kept'
                )

                assert.equal(
                    instance
                        .isLocalRoutineImportOpen,
                    false
                )

                assert.equal(
                    instance
                        .pendingLocalRoutineImport,
                    null
                )

                assert.equal(
                    browserStorage.getItem(
                        'pulse_meter_routine_import_user_10'
                    ),
                    pending.signature
                )
            }
        )
    }
)

test(
    'closes the prompt after importing the Free exercises',
    async () => {
        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        localStepsVersionA()
                    ),
            })

        const fetchMock = async () => {
            return {
                ok: true,
                status: 200,

                async json() {
                    return {
                        status:
                            'imported',

                        routine: {
                            id: 20,
                            steps: [],
                        },
                    }
                },
            }
        }

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch:
                    fetchMock,
            },
            async () => {
                const instance =
                    createStorageInstance()

                const pending =
                    instance
                        .prepareLocalRoutineImport()

                const result =
                    await instance
                        .resolveLocalRoutineImport(
                            'use_free'
                        )

                assert.equal(
                    result,
                    'imported'
                )

                assert.equal(
                    instance
                        .isLocalRoutineImportOpen,
                    false
                )

                assert.equal(
                    instance
                        .pendingLocalRoutineImport,
                    null
                )

                assert.equal(
                    browserStorage.getItem(
                        'pulse_meter_routine_import_user_10'
                    ),
                    pending.signature
                )
            }
        )
    }
)

test(
    'keeps the prompt open when Trial has reached the routine limit',
    async () => {
        const browserStorage =
            createMemoryStorage({
                pulse_meter_routine:
                    JSON.stringify(
                        localStepsVersionA()
                    ),
            })

        const fetchMock = async () => {
            return {
                ok: false,
                status: 409,

                async json() {
                    return {
                        status:
                            'not_imported',

                        reason:
                            'trial_routine_limit',
                    }
                },
            }
        }

        await withBrowserGlobals(
            {
                localStorage:
                    browserStorage,

                fetch:
                    fetchMock,
            },
            async () => {
                const instance =
                    createStorageInstance()

                instance
                    .prepareLocalRoutineImport()

                const result =
                    await instance
                        .resolveLocalRoutineImport(
                            'use_free'
                        )

                assert.equal(
                    result,
                    'trial_routine_limit'
                )

                assert.equal(
                    instance
                        .isLocalRoutineImportOpen,
                    true
                )

                assert.ok(
                    instance
                        .pendingLocalRoutineImport
                )

                assert.equal(
                    instance
                        .localRoutineImportLimitReached,
                    true
                )

                assert.equal(
                    instance
                        .localRoutineImportError,
                    null
                )
            }
        )
    }
)