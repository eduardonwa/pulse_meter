<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentQuestion extends Model
{
    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
        ];
    }
}
