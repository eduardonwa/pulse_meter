<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RandomizedSession extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'bpm' => 'integer',
            'numerator' => 'integer',
            'denominator' => 'integer',
            'subdivision' => 'integer',
            'grouping' => 'array',
            'pattern' => 'array',
        ];
    }
}
