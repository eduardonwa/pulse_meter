<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentQuestion extends Model
{
    public function knowledgeAnswer(): BelongsTo
    {
        return $this->belongsTo(KnowledgeAnswer::class);
    }

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
        ];
    }
}
