<?php

namespace App\Models;

use App\Models\PracticePlaylistItem;
use App\Models\PracticeRoutine;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticePlaylist extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function starterRoutine(): BelongsTo
    {
        return $this->belongsTo(PracticeRoutine::class, 'starter_routine_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PracticePlaylistItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }
}
