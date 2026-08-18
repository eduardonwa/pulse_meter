<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RoutineTemplate extends Model
{
    public const TYPE_ROUTINE = 'routine';
    public const TYPE_WEEKLY_CHALLENGE = 'weekly_challenge';

    protected function casts(): array
    {
        return [
            'challenge_days' => 'integer',
            'recommended_sessions' => 'integer',
        ];
    }

    // RELATIONSHIPS

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RoutineTemplateStep::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(RoutineTemplateTranslation::class);
    }

    public function spanishTranslation(): HasOne
    {
        return $this->hasOne(RoutineTemplateTranslation::class)
            ->where('locale', 'es');
    }

    public function englishTranslation(): HasOne
    {
        return $this->hasOne(RoutineTemplateTranslation::class)
            ->where('locale', 'en');
    }

    // METHODS
    
    public function translation(?string $locale = null): ?RoutineTemplateTranslation {
        $locale ??= app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations
                ->firstWhere('locale', $locale);
        }

        return $this->translations()
            ->where('locale', $locale)
            ->first();
    }
}
