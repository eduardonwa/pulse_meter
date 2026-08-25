@if ($src)
    <div style="width: 100%;">
        <iframe
            width="100%"
            height="450"
            scrolling="no"
            frameborder="0"
            allow="autoplay; encrypted-media"
            src="{{ $src }}"
        ></iframe>
    </div>
@else
    <div style="padding: 1rem;">
        SoundCloud embed
    </div>
@endif