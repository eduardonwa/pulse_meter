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
use App\Services\TrialMode\TrialAccess;
use Laravel\Cashier\Billable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    public const TRIAL_EXERCISE_LIMIT = 10;    
    public const TRIAL_EXERCISE_DURATION_LIMIT = 300;
    public const TRIAL_ROUTINE_LIMIT = 3;
    public const TRIAL_PLAYLIST_LIMIT = 1;
    
    public const PRO_EXERCISE_LIMIT = 20;
    public const PRO_EXERCISE_DURATION_LIMIT = 900;

    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable;

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
        return app(TrialAccess::class)->allows($this);
    }

    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    public function hasProAccess(): bool
    {
        return $this->isPro() || $this->hasActiveTrial();
    }

    public function lifetimeEntitlement(): HasOne
    {
        return $this->hasOne(LifetimeEntitlement::class);
    }

    public function lifetimePurchases(): HasMany
    {
        return $this->hasMany(LifetimePurchase::class);
    }

    public function hasLifetimePro(): bool
    {
        return $this
            ->lifetimeEntitlement()
            ->whereNull('revoked_at')
            ->exists();
    }

    /* PLAN LIMITS */
    public function exerciseLimit(): int
    {
        return $this->isPro()
            ? self::PRO_EXERCISE_LIMIT
            : self::TRIAL_EXERCISE_LIMIT;
    }

    public function exerciseDurationLimit(): int
    {
        return $this->isPro()
            ? self::PRO_EXERCISE_DURATION_LIMIT
            : self::TRIAL_EXERCISE_DURATION_LIMIT;
    }

    public function routineLimit(): ?int
    {
        return $this->isPro()
            ? null
            : self::TRIAL_ROUTINE_LIMIT;
    }

    public function hasReachedRoutineLimit(): bool
    {
        $limit = $this->routineLimit();

        return $limit !== null
            && $this->practiceRoutines()->count() >= $limit;
    }

    public function playlistLimit(): ?int
    {
        return $this->isPro()
            ? null
            : self::TRIAL_PLAYLIST_LIMIT;
    }

    public function hasReachedPlaylistLimit(): bool
    {
        $limit = $this->playlistLimit();

        return $limit !== null
            && $this->practicePlaylists()->count() >= $limit;
    }
}
