import {
    trackProductEvent,
    trackProductEventDebounced,
} from '../analytics/product-events'

export function analytics() {
    return {
        playbackAnalyticsStartedAt: null,
        playbackAnalyticsContext: null,
        practiceEngagementTimer: null,
        practiceEngagementRecorded: false,

        track(eventName, properties = {}) {
            return trackProductEvent(
                eventName,
                properties
            )
        },

        trackDebounced(
            key,
            eventName,
            properties = {},
            delay = 700
        ) {
            return trackProductEventDebounced(
                key,
                eventName,
                properties,
                delay
            )
        },

        beginPlaybackTracking(properties = {}) {
            clearTimeout(this.practiceEngagementTimer)

            this.playbackAnalyticsStartedAt = Date.now()
            this.playbackAnalyticsContext = properties
            this.practiceEngagementRecorded = false

            this.track('playback_started', properties)

            this.practiceEngagementTimer = setTimeout(() => {
                if (
                    !this.isPlaying
                    || this.practiceEngagementRecorded
                    || !this.playbackAnalyticsStartedAt
                ) {
                    return
                }

                this.practiceEngagementRecorded = true

                this.track('practice_engaged', {
                    ...this.playbackAnalyticsContext,
                    threshold_seconds: 50,
                })
            }, 50_000)
        },

        endPlaybackTracking(stopReason = 'user') {
            if (!this.playbackAnalyticsStartedAt) {
                return
            }

            clearTimeout(this.practiceEngagementTimer)

            const durationSeconds = Math.max(
                0,
                Math.round(
                    (
                        Date.now()
                        - this.playbackAnalyticsStartedAt
                    ) / 1000
                )
            )

            this.track('playback_stopped', {
                ...this.playbackAnalyticsContext,
                duration_seconds: durationSeconds,
                stop_reason: stopReason,
                engaged: this.practiceEngagementRecorded,
            })

            this.playbackAnalyticsStartedAt = null
            this.playbackAnalyticsContext = null
            this.practiceEngagementTimer = null
            this.practiceEngagementRecorded = false
        },
    }
}