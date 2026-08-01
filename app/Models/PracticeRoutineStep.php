<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeRoutineStep extends Model
{
    protected function casts(): array
    {
        return [
            'bpm' => 'integer',
            'duration_seconds' => 'integer',
            'position' => 'integer',
        ];
    }
    
    public function routine(): BelongsTo
    {
        return $this->belongsTo(PracticeRoutine::class, 'practice_routine_id');
    }
}