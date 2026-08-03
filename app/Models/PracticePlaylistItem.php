<?php

namespace App\Models;

use App\Models\PracticePlaylist;
use App\Models\PracticeRoutine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticePlaylistItem extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer'
        ];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(PracticePlaylist::class, 'practice_playlist_id');
    }

    public function routine(): BelongsTo
    {
        return $this->belongsTo(PracticeRoutine::class, 'practice_routine_id');
    }
}
