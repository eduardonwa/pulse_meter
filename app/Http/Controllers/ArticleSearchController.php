<?php

namespace App\Http\Controllers;

use App\Models\PostTranslation;
use App\Models\RoutineTemplateTranslation;
use App\Services\ArticleSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleSearchController extends Controller
{
    public function __invoke(
        Request $request,
        ArticleSearchService $search,
    ): JsonResponse {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:500'],
            'locale' => ['required', 'string', 'in:es,en'],
        ]);

        $articles = PostTranslation::query()
            ->whereIn('locale', ['es', 'en'])
            ->published()
            ->latest('published_at')
            ->limit(100)
            ->get();

        $routines = RoutineTemplateTranslation::query()
            ->with('routineTemplate.steps')
            ->whereIn('locale', ['es', 'en'])
            ->published()
            ->latest('published_at')
            ->limit(100)
            ->get();

        $result = $search->search(
            $validated['query'],
            $articles,
            $routines,
            $validated['locale'],
        );
        $resource = $result['resource'];
        $locale = $result['locale'] ?? $validated['locale'];

        if (! $resource) {
            return response()->json([
                'matched' => false,
                'locale' => $locale,
            ]);
        }

        $isRoutine = $result['type'] === 'routine';

        return response()->json([
            'matched' => true,
            'locale' => $locale,
            'resource' => [
                'type' => $result['type'],
                'title' => $resource->title,
                'excerpt' => $isRoutine
                    ? $resource->summary
                    : $resource->excerpt,
                'url' => route(
                    $isRoutine ? 'routines.show' : 'blog.show',
                    [
                        'locale' => $resource->locale,
                        'slug' => $resource->slug,
                    ],
                ),
            ],
        ]);
    }
}
