<?php

// app/Console/Commands/CloseOpenDriverSessions.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DriverActiveDuration;
use Carbon\Carbon;

class CloseOpenDriverSessions extends Command
{
    protected $signature = 'drivers:close-open-sessions';
    protected $description = 'Close open driver sessions older than 1 hour';

    public function handle()
    {
        $oneHourAgo = Carbon::now()->subHour();
        $openSessions = DriverActiveDuration::whereNull('inactive_at')
            ->where('active_at', '<', $oneHourAgo)
            ->get();

        foreach ($openSessions as $session) {
            $activeAt = Carbon::parse($session->active_at);
            $session->inactive_at = $activeAt->addHour();
            $session->duration_seconds = 3600; // 1 hour in seconds
            $session->save();
        }

        $this->info('Closed ' . $openSessions->count() . ' open driver sessions.');
    }
}
