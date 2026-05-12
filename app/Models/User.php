<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'avatar',
        'cover',
        'bio',
        'website',
        'location',
        'status',
        'banned_at',
        'ban_reason',
        'last_login_at',
        'last_login_ip',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'banned_at'         => 'datetime',
            'last_login_at'     => 'datetime',
            'preferences'       => 'array',
        ];
    }

    // === 标记/状态 ===
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['SUPER_ADMIN', 'ADMIN']);
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['SUPER_ADMIN', 'ADMIN', 'EDITOR']);
    }

    public function isVip(): bool
    {
        return $this->activeVip()->exists();
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return str_starts_with($this->avatar, 'http')
                ? $this->avatar
                : asset($this->avatar);
        }

        $hash = md5(strtolower(trim($this->email ?? $this->name ?? 'guest')));
        return "https://cravatar.cn/avatar/{$hash}?d=identicon&s=120";
    }

    // === 关系 ===
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'author_id');
    }

    public function authorProfile(): HasOne
    {
        return $this->hasOne(AuthorProfile::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function pointsAccount(): HasOne
    {
        return $this->hasOne(PointsAccount::class);
    }

    public function userVips(): HasMany
    {
        return $this->hasMany(UserVip::class);
    }

    public function activeVip(): HasMany
    {
        return $this->userVips()
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
