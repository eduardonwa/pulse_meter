{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>{{ url('/') }}</loc>
    </url>

    <url>
        <loc>{{ url('/en/blog') }}</loc>
    </url>

    <url>
        <loc>{{ url('/es/blog') }}</loc>
    </url>

    @foreach ($translations as $translation)
        @php
            $lastModified = $translation->updated_at->max(
                $translation->post->updated_at
            );
        @endphp

        <url>
            <loc>{{ url("/{$translation->locale}/blog/{$translation->slug}") }}</loc>
            <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
        </url>
    @endforeach

</urlset>