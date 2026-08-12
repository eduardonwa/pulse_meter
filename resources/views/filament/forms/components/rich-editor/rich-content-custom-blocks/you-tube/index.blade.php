@if ($videoId)
    <div class="youtube-embed">
        <iframe
            src="https://www.youtube-nocookie.com/embed/{{ $videoId }}"
            title="YouTube video player"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
        ></iframe>
    </div>
@else
    <p>Invalid YouTube URL.</p>
@endif