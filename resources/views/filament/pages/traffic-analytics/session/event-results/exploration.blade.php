@php
    $explorationContentId =
        $sessionId . '-exploration-content';
@endphp

<x-collapse-toggle
    label-class="collapse-toggle__label"
    label="Exploration"
    :controls="$explorationContentId"
/>

<div x-show="open"
    id="{{ $explorationContentId }}"
    x-cloak
>
    <h2>hola Dios</h2>
</div>