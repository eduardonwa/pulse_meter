<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PulsePresets extends Model
{
    protected function casts(): array
    {
        return [
            'grouping' => 'array',
            'pattern' => 'array'
        ];
    }
}
