<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;

class NotificationService
{
    /**
     * Return true if email notifications are enabled for the user
     */
    public static function emailEnabled(User $user): bool
    {
        $preferences = $user->preferences ?? [];
        // Support two shapes: root boolean or nested notifications.email
        $root = (bool) Arr::get($preferences, 'email_notifications', true);
        $nested = (bool) Arr::get($preferences, 'notifications.email', $root);
        return $nested !== false; // default true
    }

    /**
     * Return true if marketing emails are enabled
     */
    public static function marketingEnabled(User $user): bool
    {
        $preferences = $user->preferences ?? [];
        return (bool) Arr::get($preferences, 'marketing_emails', false);
    }

    /**
     * Send mailable if enabled in user preferences (global switches only)
     */
    public static function sendIfEnabled(User $user, $mailable, bool $isMarketing = false): void
    {
        try {
            if ($isMarketing) {
                if (!self::marketingEnabled($user)) {
                    return;
                }
            } else {
                if (!self::emailEnabled($user)) {
                    return;
                }
            }

            Mail::to($user->email)->send($mailable);
        } catch (\Throwable $e) {
            \Log::error('NotificationService: failed to send email', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'mailable' => is_object($mailable) ? get_class($mailable) : (string) $mailable,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send mailable for a specific event key stored in preferences.notifications
     * Example keys used by the app: suspicious_logins, password_changes, profile_changes,
     * new_features, maintenance, newsletter, special_offers
     */
    public static function sendForEvent(User $user, string $eventKey, $mailable, bool $isMarketing = false): void
    {
        try {
            $preferences = $user->preferences ?? [];

            // Global switches
            if ($isMarketing) {
                if (!self::marketingEnabled($user)) {
                    return;
                }
            } else {
                if (!self::emailEnabled($user)) {
                    return;
                }
            }

            // Granular toggle under preferences.notifications.<key>
            $notifications = $preferences['notifications'] ?? [];
            $enabled = array_key_exists($eventKey, $notifications) ? (bool) $notifications[$eventKey] : true; // default true
            if (!$enabled) {
                return;
            }

            // Optional frequency handling — if user set "never", skip for non-security events
            $frequency = $preferences['email_frequency'] ?? 'immediate';
            if ($frequency === 'never' && !in_array($eventKey, ['suspicious_logins', 'password_changes'])) {
                return;
            }

            Mail::to($user->email)->send($mailable);
        } catch (\Throwable $e) {
            \Log::error('NotificationService: failed to send event email', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'event' => $eventKey,
                'mailable' => is_object($mailable) ? get_class($mailable) : (string) $mailable,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


