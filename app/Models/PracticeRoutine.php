<?php

namespace App\Models;

use App\Models\PracticeRoutineStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeRoutine extends Model
{
    public function steps(): HasMany
    {
        return $this->hasMany(PracticeRoutineStep::class)->orderBy('order');
    }
}