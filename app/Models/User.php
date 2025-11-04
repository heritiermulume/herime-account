<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'avatar_filename',
        'company',
        'position',
        'bio',
        'location',
        'website',
        'is_active',
        'role',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    /**
     * Check if user is a super user
     */
    public function isSuperUser(): bool
    {
        return $this->role === 'super_user';
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_user']);
    }

    /**
     * Get the user's sessions.
     */
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Get the user's current session.
     */
    public function currentSession()
    {
        return $this->hasOne(UserSession::class)->where('is_current', true);
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get user's avatar URL (sécurisé - nécessite authentification).
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            // Construire l'URL vers la route sécurisée
            // On ne vérifie pas l'existence du fichier ici pour éviter les erreurs
            // La route AvatarController vérifiera l'existence
            $baseUrl = config('app.url');
            $url = rtrim($baseUrl, '/') . '/api/user/avatar/' . $this->id;
            
            \Log::info('Avatar URL generated (secure)', [
                'avatar' => $this->avatar,
                'generated_url' => $url,
                'user_id' => $this->id
            ]);
            
            return $url;
        }
        
        // Retourner l'avatar généré par défaut
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? 'User') . '&color=0ea5e9&background=e0f2fe';
    }
}
