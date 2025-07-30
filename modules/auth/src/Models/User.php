<?php

namespace Modules\Auth\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Auth\Interfaces\PasskeyUser;
use Modules\Auth\Traits\HasPasskeys;
use Modules\Auth\Traits\HasProfilePhoto;
use Modules\Auth\Traits\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements PasskeyUser
{
    use HasFactory, HasPasskeys, HasProfilePhoto, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $guard_name = 'web';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Auth\Database\Factories\UserFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'profile_photo_path',
        'last_login_at',
    ];

    protected $hidden = [
        'remember_token',
        'password',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at'           => 'datetime',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['two_factor_enabled', 'profile_photo_url'];

    /**
     * Determine if two-factor authentication is enabled.
     */
    public function getTwoFactorEnabledAttribute(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Get the profile photo URL attribute.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profilePhotoUrl();
    }
}
