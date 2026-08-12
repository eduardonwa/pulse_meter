<x-layouts.dorelog>
    <section class="container | posts" data-type="blog-post">
        <div class="flow">
            <h1 class="heading-3">Dorelog Blog</h1>
            <p class="fs-300">
                Useful ideas, practical examples, and the occasional recommendation. Open to relevant partnerships and sponsorships. <a href="/contact">Contact me</a>.
            </p>
        </div>
        
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