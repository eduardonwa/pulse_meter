<?php

namespace App\Http\Controllers;

use App\Models\PostTranslation;
use App\Services\ArticleSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleSearchController extends Controller
{
    public function __invoke(
        Request $request,
        ArticleSearchService $search,
        string $locale,
    ): JsonResponse {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $articles = PostTranslation::query()
            ->where('locale', $locale)
            ->published()
            ->latest('published_at')
            ->limit(100)
            ->get();

        $result = $search->search($validated['query'], $articles);
        $article = $result['article'];

        if (! $article) {
            return response()->json(['matched' => false]);
        }

        return response()->json([
            'matched' => true,
            'article' => [
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'url' => route('blog.show', [
                    'locale' => $locale,
                    'slug' => $article->slug,
                ]),
            ],
        ]);
    }
}
