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

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function isMember(): bool
    {
        foreach ($this->roles()->get() as $role) {
            if ($role->code === RoleEnum::MEMBER->value) {
                return true;
            }
        }
        return false;
    }

    public function isEnteredApprentice(): bool
    {
        foreach ($this->roles()->get() as $role) {
            if ($role->code === RoleEnum::ENTERED_APPRENTICE->value) {
                return true;
            }
        }
        return false;
    }

    public function isFellowcraft(): bool
    {
        foreach ($this->roles()->get() as $role) {
            if ($role->code === RoleEnum::FELLOWCRAFT->value) {
                return true;
            }
        }
        return false;
    }

    public function isMasterMason(): bool
    {
        foreach ($this->roles()->get() as $role) {
            if ($role->code === RoleEnum::MASTER_MASON->value) {
                return true;
            }
        }
        return false;
    }

    public function isOfficer(): bool
    {
        foreach ($this->roles()->get() as $role) {
            if ($role->code === RoleEnum::OFFICER->value) {
                return true;
            }
        }
        return false;
    }

    public function isSecretary(): bool
    {
        foreach ($this->roles()->get() as $role) {
            if ($role->code === RoleEnum::SECRETARY->value) {
                return true;
            }
        }
        return false;
    }

    public function isAdmin(): bool
    {
        foreach ($this->roles()->get() as $role) {
            if ($role->code === RoleEnum::ADMIN->value) {
                return true;
            }
        }
        return false;
    }
}
