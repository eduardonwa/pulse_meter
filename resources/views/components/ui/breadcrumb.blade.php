<ol>
    <li>
        <a href="{{ route('welcome') }}">
            {{ app()->isLocale('es') ? 'Inicio' : 'Home' }}
        </a>
    </li>

    <li>
        <a href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}">
            Blog
        </a>
    </li>

    <li aria-current="page">
        <span>{{ $post->title }}</span>
    </li>
</ol>