<?php

namespace App\Http\Controllers;

use App\Models\PostTranslation;
use App\Services\PostContentRenderer;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(string $locale): View
    {
        return view('blog.index', [
            'posts' => PostTranslation::query()
                ->with('post.user')
                ->where('locale', $locale)
                ->published()
                ->latest('published_at')
                ->paginate(10),
        ]);
    }

    public function show(
        PostContentRenderer $renderer,
        string $locale,
        string $slug,
    ): View
    {
        $post = PostTranslation::query()
            ->with([
                'post.user',
                'post.translations',
            ])
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();
        
        $content = $renderer->render($post->body);

        return view('blog.show', [
            'post' => $post,
            'contentHtml' => $content['html'],
            'tableOfContents' => $content['tableOfContents']
        ]);
    }
}