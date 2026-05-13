<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
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
