<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialEntitlement extends Model
{
    protected function casts(): array
    {
        return [
            'granted_seconds' => 'integer',
            'used_seconds' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'paused_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // RELATIONS
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // METHODS
    public function remainingSeconds(): int
    {
        return max(0, $this->granted_seconds - $this->used_seconds);
    }

    public function remainingTimeLabel(): string
    {
        $remaining = $this->remainingSeconds();

        $minutes = intdiv($remaining, 60);
        $seconds = $remaining % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
