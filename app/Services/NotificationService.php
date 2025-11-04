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
     * Send mailable if enabled in user preferences
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
}


