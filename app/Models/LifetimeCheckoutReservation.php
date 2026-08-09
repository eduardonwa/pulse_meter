<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifetimeCheckoutReservation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reserved_until' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOccupyingSlot(Builder $query): Builder {
        return $query->where(
            function (Builder $query): void {
                $query->whereNotNull('completed_at')->orWhere('reserved_until', '>', now());
            }
        );
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}