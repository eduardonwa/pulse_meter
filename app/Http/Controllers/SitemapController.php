<?php

namespace App\Http\Controllers;

use App\Models\PostTranslation;

class SitemapController extends Controller
{
    public function index()
    {
        $translations = PostTranslation::query()
            ->with('post')
            ->whereNotNull('slug')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        return response()
            ->view('sitemap', compact('translations'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}