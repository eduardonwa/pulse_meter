<?php

namespace App\Models;

use App\Models\PostTranslation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Post extends Model
{
    /** RELATIONS */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    public function spanishTranslation(): HasOne
    {
        return $this->hasOne(PostTranslation::class)
            ->where('locale', 'es');
    }

    public function englishTranslation(): HasOne
    {
        return $this->hasOne(PostTranslation::class)
            ->where('locale', 'en');
    }

    /** METHODS */
    public function translation(?string $locale = null): ?PostTranslation
    {
        $locale ??= app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }

        return $this->translations()
            ->where('locale', $locale)
            ->first();
    }
}
