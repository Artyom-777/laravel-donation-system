<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use LogicException;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public $timestamps = false;

    private ?string $pendingPassword = null;

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if ($user->pendingPassword === null) {
                throw new LogicException('A user cannot be created without a password.');
            }
        });

        static::saving(function (self $user): void {
            if ($user->exists && $user->pendingPassword !== null) {
                $user->roleAssignment()->firstOrFail()->update([
                    'password' => $user->pendingPassword,
                ]);
                $user->pendingPassword = null;
            }
        });

        static::created(function (self $user): void {
            if ($user->pendingPassword === null) {
                throw new LogicException('A user cannot be created without a password.');
            }

            $user->roleAssignment()->create([
                'password' => $user->pendingPassword,
                'role' => UserRole::Member,
            ]);
            $user->pendingPassword = null;
        });
    }

    public function setPasswordAttribute(string $password): void
    {
        $this->pendingPassword = Hash::needsRehash($password)
            ? Hash::make($password)
            : $password;
    }

    public function getAuthPassword(): string
    {
        return $this->roleAssignment()->firstOrFail()->password;
    }

    /**
     * Get the campaigns created by the user.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    /**
     * Get the user's role assignment.
     */
    public function roleAssignment(): HasOne
    {
        return $this->hasOne(UserRoleAssignment::class, 'user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
