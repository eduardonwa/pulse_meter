@if ($src)
    <div
        class="post--soundcloud-embed {{ $isPlaylist ? 'is-playlist' : 'is-track' }}"
    >
        <iframe
            width="100%"
            scrolling="no"
            frameborder="0"
            allow="autoplay; encrypted-media"
            src="{{ $src }}"
            loading="lazy"
        ></iframe>
    </div>
@endif