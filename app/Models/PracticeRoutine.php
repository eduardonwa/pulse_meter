<?php

namespace App\Models;

use App\Models\PracticePlaylist;
use App\Models\PracticePlaylistItem;
use App\Models\PracticeRoutineStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeRoutine extends Model
{
    public const SYNC_SOURCE_FREE_LOCAL = 'free_local';
    
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(PracticeRoutineStep::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PracticePlaylistItem::class);
    }

    public function starterForPlaylists(): HasMany
    {
        return $this->hasMany(PracticePlaylist::class, 'starter_routine_id');
    }
}