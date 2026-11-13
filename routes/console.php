<?php

use Illuminate\Support\Facades\Schedule;

// Tahap 3: sinkronisasi akreditasi BAN-PT terjadwal (cron shared hosting: * * * * * php artisan schedule:run).
Schedule::command('banpt:sync')->weeklyOn(0, '02:30')->withoutOverlapping();
Schedule::command('auth:clear-resets')->daily();
