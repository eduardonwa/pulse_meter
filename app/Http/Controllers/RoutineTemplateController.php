<?php

namespace App\Http\Controllers;

use App\Models\RoutineTemplateTranslation;
use Illuminate\View\View;

class RoutineTemplateController extends Controller
{
    public function index(string $locale): View
    {
        $routines = RoutineTemplateTranslation::query()
            ->with([
                'routineTemplate.steps',
            ])
            ->where('locale', $locale)
            ->published()
            ->latest('published_at')
            ->paginate(12);

        return view('routines.index', [
            'routines' => $routines,
        ]);
    }

    public function show(
        string $locale,
        string $slug,
    ): View {
        $routine = RoutineTemplateTranslation::query()
            ->with([
                'routineTemplate.user',
                'routineTemplate.steps',
                'routineTemplate.translations',
            ])
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        return view('routines.show', [
            'routine' => $routine,
        ]);
    }
}