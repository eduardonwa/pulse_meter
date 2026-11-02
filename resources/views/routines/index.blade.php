<x-layouts.dorelog title="{{ __('routines.title') }}" description="{{ __('routines.description') }}">
    <main class="routines-index"
        x-data="routineCatalogue(@js($routineData))"
        @keydown.escape.window="closeRoutine()"
    >
        <div class="routines-index__container container">
            <h1 class="heading-3">
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

            const sessions = Number(routine.recommendedSessions) || null;
            const days = Number(routine.challengeDays) || null;
            const isWeeklyChallenge = routine.type === 'weekly_challenge';

            let description = 'Practice this routine';

            if (sessions) {
                description += ` ${sessions} ${sessions === 1 ? 'time' : 'times'}`;
            }

            if (days) {
                description += ` over ${days} ${days === 1 ? 'day' : 'days'}`;
            } else if (isWeeklyChallenge) {
                description += ' this week';
            }

            return `${description}.`;
        },

        hasRecommendedSchedule() {
            const routine = this.selectedRoutine;

            if (!routine) {
                return false;
            }

            return (
                routine.type === 'weekly_challenge' ||
                Boolean(routine.recommendedSessions) ||
                Boolean(routine.challengeDays)
            );
        },

        scheduleLabel() {
            return this.selectedRoutine?.type === 'weekly_challenge'
                ? 'Your challenge'
                : 'Recommended schedule';
        },
    });
</script>