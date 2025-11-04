<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;
use App\Models\UserEmailNotification;

class NotificationService
{
    /**
     * Return true if email notifications are enabled for the user
     * Lit toujours depuis la base de données (pas de cache)
     */
    public static function emailEnabled(User $user): bool
    {
        // Recharger depuis la DB pour avoir les préférences à jour
        if (!$user->wasRecentlyCreated && !$user->isDirty()) {
            $user->refresh();
        }
        $preferences = $user->preferences ?? [];
        // Support two shapes: root boolean or nested notifications.email
        $root = (bool) Arr::get($preferences, 'email_notifications', true);
        $nested = (bool) Arr::get($preferences, 'notifications.email', $root);
        return $nested !== false; // default true
    }

    /**
     * Return true if marketing emails are enabled
     * Lit toujours depuis la base de données (pas de cache)
     */
    public static function marketingEnabled(User $user): bool
    {
        // Recharger depuis la DB pour avoir les préférences à jour
        if (!$user->wasRecentlyCreated && !$user->isDirty()) {
            $user->refresh();
        }
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
            // Recharger l'utilisateur depuis la DB pour avoir les préférences à jour
            if (!$user->wasRecentlyCreated && !$user->isDirty()) {
                $user->refresh();
            }
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

            // Frequency handling
            $frequency = $preferences['email_frequency'] ?? 'immediate';
            if ($frequency === 'never') {
                // Skip all except critical security could be queued elsewhere
                return;
            }

            if ($frequency === 'immediate') {
                Mail::to($user->email)->send($mailable);
                return;
            }

            // For daily/weekly/monthly: enqueue for later digest
            $scheduledAt = match ($frequency) {
                'daily' => now()->endOfDay(),
                'weekly' => now()->endOfWeek(),
                'monthly' => now()->endOfMonth(),
                default => now()->endOfDay(),
            };

            UserEmailNotification::create([
                'user_id' => $user->id,
                'event_key' => $eventKey,
                'payload' => [
                    'mailable' => get_class($mailable),
                    'data' => [
                        'firstName' => property_exists($mailable, 'firstName') ? $mailable->firstName : null,
                        'lastName' => property_exists($mailable, 'lastName') ? $mailable->lastName : null,
                        'ip' => property_exists($mailable, 'ip') ? $mailable->ip : null,
                        'device' => property_exists($mailable, 'device') ? $mailable->device : null,
                        'time' => property_exists($mailable, 'time') ? $mailable->time : null,
                        // add other fields as needed
                    ],
                ],
                'scheduled_at' => $scheduledAt,
            ]);
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

    /**
     * Send a queued notification item (single event mail)
     */
    public static function sendQueued(UserEmailNotification $item): bool
    {
        $user = User::find($item->user_id);
        if (!$user) {
            $item->sent_at = now();
            $item->save();
            return false;
        }

        // Respect global email switch
        if (!self::emailEnabled($user)) {
            $item->sent_at = now();
            $item->save();
            return false;
        }

        try {
            $payload = $item->payload ?? [];
            $mailableClass = $payload['mailable'] ?? null;
            if ($mailableClass && class_exists($mailableClass)) {
                $data = $payload['data'] ?? [];
                $mailable = new $mailableClass(...array_values($data));
                Mail::to($user->email)->send($mailable);
            }
            $item->sent_at = now();
            $item->save();
            return true;
        } catch (\Throwable $e) {
            \Log::error('NotificationService: failed to send queued item', [
                'id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            // Do not rethrow to avoid blocking the batch
            $item->sent_at = now();
            $item->save();
            return false;
        }
    }
}


