<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('audit:prune')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
Schedule::command('auth:clear-resets')->daily();
Schedule::command('queue:prune-failed --hours=168')->daily();
