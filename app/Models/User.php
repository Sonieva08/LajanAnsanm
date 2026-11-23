<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\EmailVerificationCodeService;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    // is_owner, last_seen_at et disabled_at ne sont volontairement PAS ici : impossibles à forcer depuis un formulaire
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    public function sendEmailVerificationNotification(): void
    {
        app(EmailVerificationCodeService::class)->send($this);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_owner' => 'boolean',
            'last_seen_at' => 'datetime',
            'disabled_at' => 'datetime',
            'google_linked_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function tontines(): HasMany
    {
        return $this->hasMany(Tontine::class, 'admin_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function subscriptionRequests(): HasMany
    {
        return $this->hasMany(SubscriptionRequest::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isOwner(): bool
    {
        return (bool) $this->is_owner;
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }
}
