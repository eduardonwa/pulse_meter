<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedRoutine extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function publicUrl(string $locale = 'en'): string
    {
        return route('shared.show', [
            'locale' => $locale,
            'token' => $this->slug ?: $this->token,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routine(): BelongsTo
    {
        return $this->belongsTo(PracticeRoutine::class, 'practice_routine_id');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(PracticeRoutine::class);
    }
}
