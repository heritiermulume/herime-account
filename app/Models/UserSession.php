<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'token_id',
        'ip_address',
        'user_agent',
        'device_name',
        'platform',
        'browser',
        'is_current',
        'last_activity',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'last_activity' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get device information from user agent.
     */
    public function getDeviceInfo(): array
    {
        $userAgent = $this->user_agent;
        
        // Simple device detection
        $platform = 'Unknown';
        $browser = 'Unknown';
        
        if (strpos($userAgent, 'Windows') !== false) {
            $platform = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $platform = 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $platform = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $platform = 'Android';
        } elseif (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            $platform = 'iOS';
        }
        
        if (strpos($userAgent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $browser = 'Edge';
        }
        
        return [
            'platform' => $platform,
            'browser' => $browser,
        ];
    }
}
