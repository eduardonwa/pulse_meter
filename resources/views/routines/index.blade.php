<x-layouts.dorelog>
    <h1>Routines</h1>

    @foreach ($routines as $routine)
        <a href="{{ route('routines.show', [
            'locale' => app()->getLocale(),
            'slug' => $routine->slug,
        ]) }}">
            {{ $routine->title }}
        </a>
    @endforeach
</x-layouts.dorelog>