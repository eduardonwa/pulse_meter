<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineTemplateStep extends Model
{
    protected function casts(): array
    {
        return [
            'bpm' => 'integer',
            'duration_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    public function routineTemplate(): BelongsTo
    {
        return $this->belongsTo(RoutineTemplate::class);
    }
}
