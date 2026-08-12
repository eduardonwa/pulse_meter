<x-layouts.dorelog>
    <div class="container | breadcrumbs" data-type="blog-post" aria-label="Breadcrumb">
        <x-ui.breadcrumb :post="$post" />
    </div>
    
    <article class="container | post" data-type="blog-post">
        <header class="post__header">
            <h1 class="heading-2">{{ $post->title }}</h1>

            <div class="info">
                <p>{{ $post->published_at->format('F j, Y') }}</p>
                by
                <a href="">{{ $post->post->user->name }}</a>
            </div>

            <p class="excerpt">{{ $post->excerpt }}</p>
        </header>
        
        @if ($post->post->thumbnail)
            <img class="thumbnail" alt="{{ $post->thumb_alt ?: $post->title }}"
                src="{{ Storage::disk('public')->url($post->post->thumbnail) }}"
                width="1600"
                height="900"
            >
        @endif

        @if(count($tableOfContents) > 1)
            <nav class="table-of-contents" aria-labelledby="table-of-contents-title">
                <h2 class="header" id="table-of-contents-title">
                    {{ app()->isLocale('es') ? 'Contenido' : 'Contents'}}
                </h2>

                <ol class="items">
                    @foreach ($tableOfContents as $item)
                        <li data-level="{{ $item['level'] }}">
                            <a href="#{{ $item['id'] }}">
                                {{ $item['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <div class="post__body">
            {!! $contentHtml !!}
        </div>
    </article>
</x-layouts.dorelog>