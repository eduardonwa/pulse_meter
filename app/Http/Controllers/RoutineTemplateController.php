<?php

namespace App\Http\Controllers;

use App\Models\RoutineTemplateTranslation;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RoutineTemplateController extends Controller
{
    public function index(string $locale)
    {
        $routines = RoutineTemplateTranslation::query()
            ->with([
                'routineTemplate.steps' => fn ($query) =>
                    $query->orderBy('position'),
            ])
            ->where('locale', $locale)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        $routineData = $routines->mapWithKeys(function ($routine) use ($locale) {
            $template = $routine->routineTemplate;
            $steps = $template->steps;

            $totalSeconds = $steps->sum(
                fn ($step) =>
                    $step->mode === 'timer'
                        ? ($step->duration_seconds ?? 0)
                        : 0
            );

            return [
                $template->id => [
                    'id' => $template->id,

                    'title' => $routine->title,
                    'slug' => $routine->slug,
                    'summary' => $routine->summary,
                    'purpose' => $routine->purpose,
                    'instructions' => $routine->instructions,

                    'coverAlt' => $routine->cover_alt ?: $routine->title,
                    'coverUrl' => $template->cover_image
                        ? Storage::disk('public')->url($template->cover_image)
                        : asset('og-image.png'),

                    'type' => $template->type,
                    'typeLabel' => (string) str($template->type)
                        ->replace('_', ' ')
                        ->title(),

                    'instrument' => (string) str($template->instrument)->title(),
                    'difficulty' => (string) str($template->difficulty)->title(),

                    'challengeDays' => $template->challenge_days,
                    'recommendedSessions' => $template->recommended_sessions,

                    'totalSeconds' => $totalSeconds,
                    'totalMinutes' => (int) ceil($totalSeconds / 60),
                    'exercisesCount' => $steps->count(),

                    'showUrl' => route('routines.show', [
                        'locale' => $locale,
                        'slug' => $routine->slug,
                    ]),

                    'steps' => $steps->map(function ($step) use ($locale) {
                        return [
                            'id' => $step->id,

                            'name' => $locale === 'en'
                                ? ($step->name_en ?: $step->name_es)
                                : $step->name_es,

                            'notes' => $locale === 'en'
                                ? ($step->notes_en ?: $step->notes_es)
                                : $step->notes_es,

                            'bpm' => $step->bpm,
                            'mode' => $step->mode,
                            'modeLabel' => (string) str($step->mode)->title(),
                            'durationSeconds' => $step->duration_seconds,
                        ];
                    })->values()->all(),
                ],
            ];
        })->all();

        return view('routines.index', compact(
            'routines',
            'routineData',
        ));
    }

    public function show(string $locale,string $slug): View {
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