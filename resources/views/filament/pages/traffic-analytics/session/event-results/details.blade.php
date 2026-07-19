@php
    $sessionDetailsContentId =
        $sessionId . '-session-details-content';
@endphp

<x-collapse-toggle
    label-class="collapse-toggle__label"
    label="Session details"
    :controls="$sessionDetailsContentId"
/>

@foreach ($productSessions as $session)
    <div class="product-session__details"
        id="{{ $sessionDetailsContentId }}"
        x-show="open"
        x-cloak
    >
        <div class="field">
            <span class="label"> Highest stage </span>
            <p class="value">
                {{
                    str($session['highest_stage'] ?? 'visit')
                        ->replace('_', ' ')
                        ->title()
                }}
            </p>
        </div>

        <div class="field">
            <span class="label"> Visitor ID </span>
            <p class="value"> {{ $session['visitor_id'] ?? 'Unknown visitor' }} </p>
        </div>

        <div class="field">
            <span class="label"> Product session </span>
            <p class="value"> {{ $session['session_id'] ?? 'Unknown session' }} </p>
        </div>
    </div>
@endforeach