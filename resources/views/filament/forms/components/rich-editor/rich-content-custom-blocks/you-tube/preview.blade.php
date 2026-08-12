@if ($videoId)
    <div class="youtube-embed youtube-preview">
        <iframe
            src="https://www.youtube-nocookie.com/embed/{{ $videoId }}"
            title="YouTube video preview"
            loading="lazy"
            allowfullscreen
        ></iframe>
    </div>
@endif