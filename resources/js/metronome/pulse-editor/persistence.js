export function persistence() {
    return {

        // PATTERN PAYLOAD
        getPulsePatternPayload() {
            const snapshot = this.getPulseSnapshot()

            return {
                name: `${snapshot.timeSignature.numerator}/${snapshot.timeSignature.denominator} — ${snapshot.grouping.join(' + ')}`,
                
                numerator: snapshot.timeSignature.numerator,
                denominator: snapshot.timeSignature.denominator,
                grouping: snapshot.grouping,
                pattern: snapshot.pattern,
            }
        },

        // PATTERN CREATION
        async storePulsePattern() {
            const payload = this.getPulsePatternPayload()

            const response = await fetch(
                this.$root.dataset.pulsePatternsStoreUrl,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',

                        'X-CSRF-TOKEN': document
                            .querySelector(
                                'meta[name="csrf-token"]'
                            )
                            .content,
                    },

                    body: JSON.stringify(payload),
                }
            )

            if (!response.ok) {
                console.error(
                    'Could not store pulse pattern.',
                    await response.json()
                )

                return null
            }

            return await response.json()
        },

        // SAVE ACTIONS
        async savePulsePattern() {
            if (this.pulseDraft.origin === 'preset') {
                return false
            }

            if (this.pulseDraft.origin === 'new') {
                const userPattern = await this.storePulsePattern()

                if (!userPattern) {
                    this.showToast('Could not save pattern', 'error')
                    return false
                }

                this.userPatterns.push(userPattern)

                this.pulseDraft = {
                    origin: 'user',
                    sourceId: userPattern.id,
                    isDirty: false,
                }

                this.setPulseBaseline()

                this.showToast('Pattern saved', 'success')

                return true
            }

            if (this.pulseDraft.origin === 'user') {
                const userPattern =
                    await this.updatePulsePattern(
                        this.pulseDraft.sourceId
                    )

                if (!userPattern) {
                    this.showToast('Could not update pattern', 'error')
                    return false
                }

                const index = this.userPatterns.findIndex(
                    pattern => pattern.id === userPattern.id
                )

                if (index !== -1) {
                    this.userPatterns.splice(
                        index,
                        1,
                        userPattern
                    )
                }

                this.pulseDraft = {
                    origin: 'user',
                    sourceId: userPattern.id,
                    isDirty: false,
                }

                this.setPulseBaseline()

                this.showToast('Pattern updated', 'success')

                return true
            }

            return false
        },

        async savePulsePatternAs() {
            if (this.pulseDraft.origin === 'new') {
                return false
            }

            const userPattern = await this.storePulsePattern()

            if (!userPattern) {
                this.showToast('Could not save pattern as new','error')
                return false
            }

            this.userPatterns.push(userPattern)

            this.pulseDraft = {
                origin: 'user',
                sourceId: userPattern.id,
                isDirty: false,
            }

            this.setPulseBaseline()
            this.showToast('Pattern saved as new', 'success')

            return true
        },

        // PATTERN LOADING
        async loadPulsePatterns() {
            const response = await fetch(
                this.$root.dataset.pulsePatternsIndexUrl,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                }
            )

            if (!response.ok) {
                console.error(
                    'Could not load pulse patterns.'
                )

                return false
            }

            this.userPatterns = await response.json()

            return true
        },

        // PATTERN UPDATES
        async updatePulsePattern(id) {
            const payload = this.getPulsePatternPayload()

            const url = this.$root.dataset
                .pulsePatternsUpdateUrl
                .replace('__ID__', id)

            const response = await fetch(
                url,
                {
                    method: 'PATCH',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',

                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .content,
                    },

                    body: JSON.stringify(payload),
                }
            )

            if (!response.ok) {
                console.error(
                    'Could not update pulse pattern.',
                    await response.json()
                )

                return null
            }

            return await response.json()
        },

        // PATTERN DELETION
        async destroyPulsePattern(id) {
            const url = this.$root.dataset
                .pulsePatternsDestroyUrl
                .replace('__ID__', id)

            const response = await fetch(
                url,
                {
                    method: 'DELETE',

                    headers: {
                        'Accept': 'application/json',

                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .content,
                    },
                }
            )

            if (!response.ok) {
                console.error(
                    'Could not delete pulse pattern.',
                    await response.json()
                )

                this.showToast('Could not delete pattern', 'error')

                return false
            }

            this.userPatterns = this.userPatterns.filter(
                pattern => pattern.id !== id
            )

            this.showToast('Pattern deleted', 'success')

            return true
        },

        async deleteCurrentPulsePattern() {
            if (this.pulseDraft.origin !== 'user') {
                return false
            }

            const deleted = await this.destroyPulsePattern(
                this.pulseDraft.sourceId
            )

            if (!deleted) {
                return false
            }

            this.startNewPattern()

            return true
        },
    }
}