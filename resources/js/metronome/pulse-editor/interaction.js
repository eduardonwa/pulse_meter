export function interaction() {
    return {
        // EDITOR TOOLS
        applyEditorTool(beat) {
            if (!this.editorTool) {
                return
            }

            if (this.editorTool === 'groupStart') {
                const patternBeat = this.pattern[beat - 1]

                if (!patternBeat) {
                    return
                }

                this.setGroupStart(
                    beat,
                    !patternBeat.groupStart
                )

                this.cancelToolTether()

                return
            }

            this.setPatternBeat(
                beat,
                this.editorTool
            )

            this.cancelToolTether()
        },

        applyEditorToolToSubdivision(beat, subdivisionIndex) {
            if (!this.editorTool) {
                return
            }

            if (this.editorTool === 'groupStart') {
                const item = this.pattern[beat - 1]?.subdivisions?.[subdivisionIndex]
                if (!item) return

                this.setGroupStart(
                    beat,
                    !Boolean(item.groupStart),
                    null,
                    subdivisionIndex
                )
                this.cancelToolTether()
                return
            }

            this.setPatternSubdivision(
                beat,
                subdivisionIndex,
                this.editorTool
            )

            this.cancelToolTether()
        },

        // TOOL TETHER
        startToolTether(event) {
            const canHover = window.matchMedia(
                '(hover: hover) and (pointer: fine)'
            ).matches

            if (!canHover) { return }

            const rect =
                event.currentTarget.getBoundingClientRect()

            this.toolTether.startX =
                rect.left + rect.width / 2

            this.toolTether.startY =
                rect.top + rect.height / 2

            this.toolTether.endX =
                this.toolTether.startX

            this.toolTether.endY =
                this.toolTether.startY

            this.toolTether.active = true
        },

        getToolTetherPath() {
            const {
                startX,
                startY,
                endX,
                endY,
            } = this.toolTether

            const distance =
                Math.abs(endX - startX)

            const curve =
                Math.max(60, distance * 0.35)

            return `
                M ${startX} ${startY}
                C ${startX + curve} ${startY},
                  ${endX - curve} ${endY},
                  ${endX} ${endY}
            `
        },

        handleToolTetherPointerMove(event) {
            if (!this.toolTether.active) {
                return
            }

            this.toolTether.endX = event.clientX
            this.toolTether.endY = event.clientY
        },

        handleToolTetherClick(event) {
            const hasActiveTool =
                this.editorTool
                || this.randomizerEditorTool

            if (!hasActiveTool) {
                return
            }

            if (
                event.target.closest(
                    '[data-tether-trigger]'
                )
            ) {
                return
            }

            this.cancelToolTether()
        },

        cancelToolTether() {
            this.toolTether.active = false
            this.editorTool = null

            if (
                Object.hasOwn(
                    this,
                    'randomizerEditorTool'
                )
            ) {
                this.randomizerEditorTool = null
            }
        },

        // PATTERN DIALOG
        openPatternDialog() {
            if (!this.activePatternTab) {
                const collections = this.getPulsePresetCollections()

                this.activePatternTab =
                    collections[0]
                    ?? (this.userPatterns.length ? 'user' : null)
            }
            
            this.isPatternDialogOpen = true

            this.$nextTick(() => {
                this.$refs.patternDialog?.showModal()
            })
        },

        closePatternDialog() {
            this.cancelRenamingPattern()
            
            this.isPatternDialogOpen = false
            this.$refs.patternDialog?.close()
        },

        selectPulseSourceFromDialog(value) {
            this.selectPulseSource(value)
            this.closePatternDialog()
        },

        // PATTERN NAMING DIALOG
        openPulsePatternNameDialog(action) {
            if (!['new', 'copy'].includes(action)) return false

            this.pulsePatternNameAction = action
            this.pulsePatternNameInput = action === 'copy'
                ? String(this.patternName ?? '')
                : ''
            this.isPulsePatternNameDialogOpen = true

            this.$nextTick(() => {
                this.$refs.pulsePatternNameDialog?.showModal()
                this.$refs.pulsePatternNameInput?.focus()
                this.$refs.pulsePatternNameInput?.select()
            })

            return true
        },

        closePulsePatternNameDialog() {
            if (this.isPulsePatternNameSaving) return false

            this.isPulsePatternNameDialogOpen = false
            this.$refs.pulsePatternNameDialog?.close()
            this.pulsePatternNameAction = null
            this.pulsePatternNameInput = ''
            return true
        },

        async submitPulsePatternName() {
            const name = this.pulsePatternNameInput.trim()
            const action = this.pulsePatternNameAction

            if (!name || name.length > 80 || this.isPulsePatternNameSaving) {
                return false
            }

            if (!['new', 'copy'].includes(action)) return false

            const previousName = this.patternName
            this.isPulsePatternNameSaving = true
            this.patternName = name

            try {
                const saved = action === 'new'
                    ? await this.savePulsePattern()
                    : await this.savePulsePatternAs()

                if (!saved) {
                    this.patternName = previousName
                    return false
                }

                this.isPulsePatternNameSaving = false
                this.closePulsePatternNameDialog()
                return true
            } catch (error) {
                this.patternName = previousName
                console.error('Could not save named pulse pattern.', error)
                this.showToast('Could not save pattern', 'error')
                return false
            } finally {
                this.isPulsePatternNameSaving = false
            }
        },

        // DELETE DIALOG
        openDeletePatternDialog(id) {
            this.patternPendingDeleteId = id
            this.showDeletePatternDialog = true

            this.$nextTick(() => {
                this.$refs.deletePatternDialog?.showModal()
            })
        },

        closeDeletePatternDialog() {
            this.showDeletePatternDialog = false
            this.patternPendingDeleteId = null

            this.$refs.deletePatternDialog?.close()
        },

        async confirmDeletePattern() {
            if (!this.patternPendingDeleteId) {
                return false
            }

            const id = this.patternPendingDeleteId

            const isCurrentPattern =
                this.pulseDraft.origin === 'user'
                && this.pulseDraft.sourceId === id

            const deleted = await this.destroyPulsePattern(id)

            if (!deleted) {
                return false
            }

            this.closeDeletePatternDialog()

            if (isCurrentPattern) {
                this.startNewPattern()
            }

            return true
        },

        // INLINE PATTERN RENAME
        startRenamingPattern(pattern) {
            this.patternPendingRenameId = Number(pattern.id)
            this.patternRenameName = String(pattern.name ?? '')
        },

        cancelRenamingPattern() {
            this.patternPendingRenameId = null
            this.patternRenameName = ''
        },

        async submitPatternRename() {
            const id = Number(this.patternPendingRenameId)
            const name = this.patternRenameName.trim()

            if (!id || !name) { return false }

            const renamed = await this.renamePulsePattern(id, name)

            if (!renamed) { return false }

            this.cancelRenamingPattern()

            return true
        },
    }
}
