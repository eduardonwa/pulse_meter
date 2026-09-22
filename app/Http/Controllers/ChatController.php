<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;

class ChatController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $defaultLocale = in_array(config('app.locale'), ['es', 'en'], true)
            ? config('app.locale')
            : 'en';
        $supportedLocales = array_values(array_unique([
            $defaultLocale,
            'es',
            'en',
        ]));
        $locale = $request->getPreferredLanguage($supportedLocales)
            ?? $defaultLocale;

        App::setLocale($locale);

        return response()
            ->view('chat.index', compact('locale'))
            ->header('Vary', 'Accept-Language');
    }
}
