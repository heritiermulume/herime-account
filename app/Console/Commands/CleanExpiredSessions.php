<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserSession;
use App\Models\SystemSetting;

class CleanExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired user sessions based on timeout setting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeoutHours = (int)SystemSetting::get('session_timeout', 24);
        $expiredDate = now()->subHours($timeoutHours);
        
        $deleted = UserSession::where(function($query) use ($expiredDate) {
                $query->where('last_activity', '<', $expiredDate)
                      ->orWhere(function($q) use ($expiredDate) {
                          $q->whereNull('last_activity')
                            ->where('created_at', '<', $expiredDate);
                      });
            })
            ->delete();
        
        $this->info("Cleaned {$deleted} expired sessions (timeout: {$timeoutHours} hours)");
        
        return Command::SUCCESS;
    }
}
