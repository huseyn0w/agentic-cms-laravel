<?php

namespace App\Http\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract, OAuthenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * Create a new factory instance for the model.
     *
     * The model lives in a non-standard namespace (App\Http\Models), so the
     * default factory name guesser cannot resolve it automatically.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'role_id',
        'password',
        'username',
        'city',
        'country',
        'gender',
        'avatar',
        'about_me',
        'linkedin_url',
        'xing_url',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'google_url',
    ];

    // NOTE: `provider`/`provider_id` are intentionally NOT mass assignable;
    // they are owned by the social-login flow and set explicitly in
    // LoginController::createUser(). `role_id` stays fillable so admins can
    // assign roles on creation, but CPanelUserRepository::update() strips it
    // from any self-service profile update.

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
    ];

    /**
     * 2FA is active only once a secret exists AND the user has proven a code.
     */
    public function hasEnabledTwoFactor(): bool
    {
        return ! empty($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Add a mutator to ensure hashed passwords
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    public function role()
    {
        return $this->hasOne('App\Http\Models\UserRoles', 'id', 'role_id');
    }

    public function isAdmin()
    {
        return $this->role->id === 1;
    }

    public function permissions()
    {
        return $this->role->permissions;
    }

    public function likes()
    {
        return $this->hasMany(Likes::class);
    }

    public function post()
    {
        return $this->hasMany(Post::class);
    }
}
