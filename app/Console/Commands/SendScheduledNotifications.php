<?php

namespace App\Console\Commands;

use App\Models\UserEmailNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendScheduledNotifications extends Command
{
    protected $signature = 'notifications:send-scheduled';

    protected $description = 'Send due scheduled user email notifications';

    public function handle(): int
    {
        $now = now();
        $due = UserEmailNotification::whereNull('sent_at')
            ->where('scheduled_at', '<=', $now)
            ->limit(200)
            ->get();

        $count = 0;
        foreach ($due as $item) {
            $ok = NotificationService::sendQueued($item);
            if ($ok) {
                $count++;
            }
        }

        $this->info("Sent {$count} scheduled notifications.");
        return self::SUCCESS;
    }
}

