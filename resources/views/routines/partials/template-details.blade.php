<section class="routine-player__details">
    <h2 class="routine-player__section-title">
        About this routine
    </h2>

    <dl class="routine-player__facts">
        <div class="routine-player__fact">
            <dt>Instrument</dt>

            <dd>
                {{ str($template->instrument)->title() }}
            </dd>
        </div>

        <div class="routine-player__fact">
            <dt>Difficulty</dt>

            <dd>
                {{ str($template->difficulty)->title() }}
            </dd>
        </div>
        
        @if ($totalSeconds > 0)
            <div class="routine-player__fact">
                <dt>Duration</dt>

                <dd>{{ $totalMinutes }} minutes</dd>
            </div>
        @endif

        <div class="routine-player__fact">
            <dt>Exercises</dt>

            <dd>{{ $template->steps->count() }}</dd>
        </div>
    </dl>

    <div class="routine-player__description">
        <h3>Summary</h3>

        <p>{{ $routine->summary }}</p>
    </div>

    @if ($routine->purpose)
        <div class="routine-player__description">
            <h3>Purpose</h3>

            <p>{{ $routine->purpose }}</p>
        </div>
    @endif

    @if ($routine->instructions)
        <div class="routine-player__description">
            <h3>How to use it</h3>

            <p>{{ $routine->instructions }}</p>
        </div>
    @endif
</section>