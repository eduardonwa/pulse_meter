<x-layouts.dorelog title="{{ __('blog.title') }}" description="{{ __('blog.description') }}">
    <section class="container | posts" data-type="blog-post">
        <div class="flow">

            <x-language-selector route-name="blog.index" />

            <h1 class="heading-3">Dorelog Blog</h1>
            <div class="fs-300">
                {{ __('blog.subheader') }} <a href="">{{ __('blog.contact_link') }}</a>
            </div>
        </div>

        {{-- Chat CTA hidden during navigation simplification. --}}

        @foreach ($posts as $post)
            <a class="posts__item" href="{{ route('blog.show', [
                'locale' => app()->getLocale(),
                'slug' => $post->slug,
            ]) }}">
                <span class="posts__pattern" aria-hidden="true"></span>

                <h2 class="heading-3">{{ $post->title }}</h2>

                <p>{{ $post->excerpt }}</p>
                <p>{{ $post->published_at->format('F j, Y') }}</p>
            </a>
        @endforeach
    </section>
</x-layouts.dorelog>
