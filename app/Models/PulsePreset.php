<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PulsePreset extends Model
{
    protected function casts(): array
    {
        return [
            'numerator' => 'integer',
            'denominator' => 'integer',
            'grouping' => 'array',
            'pattern' => 'array'
        ];
    }
}
