<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    public const STATUS_ACTIVO = 'activo';
    public const STATUS_SUSPENDIDO = 'suspendido';

    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isEntrepreneur(): bool
    {
        return $this->hasRole('entrepreneur');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVO;
    }

    public function entrepreneurProfile()
    {
        return $this->hasOne(EntrepreneurProfile::class);
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function accessibilityPreference()
    {
        return $this->hasOne(AccessibilityPreference::class);
    }

    public function businesses()
    {
        return $this->hasManyThrough(Business::class, EntrepreneurProfile::class);
    }

    public function getsAccessibilityPreference(): AccessibilityPreference
    {
        return $this->accessibilityPreference ?? AccessibilityPreference::defaultFor($this);
    }

    public function getFirstNameOrNameAttribute(): string
    {
        return $this->first_name ?: $this->name;
    }
}