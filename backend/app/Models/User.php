<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'wallet_balance',
        'preferred_locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'wallet_balance'    => 'decimal:2',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest();
    }

    public function activeAgreement(): HasOne
    {
        return $this->hasOne(BkashAgreement::class)->where('status', 'active')->latestOfMany();
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(BkashAgreement::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function hasActiveAgreement(): bool
    {
        return $this->activeAgreement()->exists();
    }
}
