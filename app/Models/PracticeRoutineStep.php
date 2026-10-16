<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeRoutineStep extends Model
{
    protected function mode(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === 'timer' ? 'drill' : $value,
        );
    }

    protected function casts(): array
    {
        return [
            'bpm' => 'integer',
            'time_signature_numerator' => 'integer',
            'time_signature_denominator' => 'integer',
            'duration_seconds' => 'integer',
            'position' => 'integer',
        ];
    }
    
    public function routine(): BelongsTo
    {
        return $this->belongsTo(PracticeRoutine::class, 'practice_routine_id');
    }
}