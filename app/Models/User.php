<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\PracticePlaylist;
use App\Models\PracticeRoutine;
use App\Models\PulsePreset;
use App\Models\TrialEntitlement;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    public const TRIAL_EXERCISE_LIMIT = 10;
    public const PRO_EXERCISE_LIMIT = 20;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // RELACIONES
    public function trialEntitlement(): HasOne
    {
        return $this->hasOne(TrialEntitlement::class);
    }

    public function pulsePresets(): HasMany
    {
        return $this->hasMany(PulsePreset::class);
    }

    public function practiceRoutines()
    {
        return $this->hasMany(PracticeRoutine::class);
    }

    public function practicePlaylists(): HasMany
    {
        return $this->hasMany(PracticePlaylist::class);
    }

    // ADMIN
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin
            && $this->email === 'eduardongua@hotmail.com';
    }

    // METHODS **
    
    // ACCESS
    public function hasActiveTrial(): bool
    {
        $trial = $this->trialEntitlement;

        if (! $trial) {
            return false;
        }

        return $trial->status === 'active'
            && $trial->expires_at->isFuture()
            && $trial->used_seconds < $trial->granted_seconds;
    }

    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    public function hasProAccess(): bool
    {
        return $this->isPro() || $this->hasActiveTrial();
    }

    public function exerciseLimit(): int
    {
        return $this->isPro()
            ? self::PRO_EXERCISE_LIMIT
            : self::TRIAL_EXERCISE_LIMIT;
    }
}
