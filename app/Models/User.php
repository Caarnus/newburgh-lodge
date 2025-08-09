<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\RoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'member',
        'degree',
        'officer',
        'secretary',
        'admin',
    ];

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
        ];
    }

    public function getMemberAttribute(): bool
    {
        return $this->isMember();
    }

    public function getDegreeAttribute(): RoleEnum
    {
        if ($this->isMasterMason())
            return RoleEnum::MASTER_MASON;
        elseif ($this->isFellowcraft())
            return RoleEnum::FELLOWCRAFT;
        elseif ($this->isEnteredApprentice())
            return RoleEnum::ENTERED_APPRENTICE;
        else
            return RoleEnum::NONE;
    }

    public function getOfficerAttribute(): bool
    {
        return $this->isOfficer();
    }

    public function getSecretaryAttribute(): bool
    {
        return $this->isSecretary();
    }

    public function getAdminAttribute(): bool
    {
        return $this->isAdmin();
    }

    public function isMember(): bool
    {
        return $this->hasRole(RoleEnum::MEMBER);
    }

    public function isEnteredApprentice(): bool
    {
        return $this->hasRole(RoleEnum::ENTERED_APPRENTICE);
    }

    public function isFellowcraft(): bool
    {
        return $this->hasRole(RoleEnum::FELLOWCRAFT);
    }

    public function isMasterMason(): bool
    {
        return $this->hasRole(RoleEnum::MASTER_MASON);
    }

    public function isOfficer(): bool
    {
        return $this->hasRole(RoleEnum::OFFICER);
    }

    public function isSecretary(): bool
    {

        return $this->hasRole(RoleEnum::SECRETARY);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleEnum::ADMIN);
    }
}
