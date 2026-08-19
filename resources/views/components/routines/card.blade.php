@props([
    'routine',
    'template',
])

@php
    $typeLabel = str($template->type)
        ->replace('_', ' ')
        ->title();

    $coverUrl = $template->cover_image
        ? Storage::disk('public')->url($template->cover_image)
        : null;
@endphp

<x-routines.cover
    class="routine-card"
    :routine="$routine"
    :template="$template"
    :cover-url="$coverUrl"
    :type-label="$typeLabel"
    :catalogue="true"
/>