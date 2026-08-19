<x-layouts.dorelog>
    <main class="routines-index"
        x-data="routineCatalogue(@js($routineData))"
        @keydown.escape.window="closeRoutine()"
    >
        <div class="routines-index__container container">
            <h1 class="routines-index__title">
                Routines
            </h1>

            <div class="routine-grid">
                @foreach ($routines as $routine)
                    <x-routines.card
                        :routine="$routine"
                        :template="$routine->routineTemplate"
                    />
                @endforeach
            </div>
        </div>

        <x-routines.drawer />
    </main>
</x-layouts.dorelog>

<script>
    window.routineCatalogue = (routines) => ({
        routines,
        selectedRoutine: null,
        drawerOpen: false,
        closeTimer: null,

        openRoutine(templateId) {
            window.clearTimeout(this.closeTimer);

            const routine = this.routines[String(templateId)];

            if (!routine) {
                return;
            }

            this.selectedRoutine = routine;
            this.drawerOpen = true;

            this.$nextTick(() => {
                this.$refs.closeButton?.focus();
            });
        },

        closeRoutine() {
            this.drawerOpen = false;

            this.closeTimer = window.setTimeout(() => {
                this.selectedRoutine = null;
            }, 300);
        },

        formatDuration(duration) {
            const seconds = Number(duration);

            if (!seconds) {
                return '';
            }

            if (seconds < 60) {
                return `${seconds} sec`;
            }

            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;

            if (remainingSeconds === 0) {
                return `${minutes} min`;
            }

            return `${minutes} min ${remainingSeconds} sec`;
        },

        challengeDescription() {
            const routine = this.selectedRoutine;

            if (!routine) {
                return '';
            }

            let description = 'Practice this routine';

            if (routine.recommendedSessions) {
                description += ` ${routine.recommendedSessions} times`;
            }

            if (routine.challengeDays) {
                description += ` over ${routine.challengeDays} days.`;
            } else {
                description += ' this week.';
            }

            return description;
        },
    });
</script>