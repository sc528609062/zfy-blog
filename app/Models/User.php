<?php

namespace App\Models;

use App\Notifications\ResetAccountPassword;
use App\Notifications\VerifyAccountEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    protected static function booted(): void
    {
        static::saving(function (self $user) {
            zfy_validate('zfy_user_saving', $user, array_diff_key($user->getDirty(), ['password' => true, 'remember_token' => true]));
        });
        static::saved(function (self $user) {
            zfy_after_commit('zfy_user_saved', $user, array_diff_key($user->getChanges(), ['password' => true, 'remember_token' => true]));
        });
    }

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'avatar_url',
        'bio',
        'is_author',
        'author_status',
        'meta',
        'is_banned',
        'ban_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'is_author' => 'boolean',
            'is_banned' => 'boolean',
            'meta' => 'array',
        ];
    }

    public static function findForLogin(string $login): ?self
    {
        $login = trim($login);

        if ($login === '') {
            return null;
        }

        $column = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return self::where($column, $login)->first();
    }

    public function contents()
    {
        return $this->hasMany(Content::class, 'author_id');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyAccountEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetAccountPassword($token));
    }

    public function canSubmitContent(): bool
    {
        return ! $this->is_banned && (($this->is_author && $this->author_status === 'approved') || $this->can('publish contents') || $this->can('manage contents'));
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function pointsAccount()
    {
        return $this->hasOne(PointsAccount::class);
    }

    public function vip()
    {
        return $this->hasOne(UserVip::class);
    }
}
