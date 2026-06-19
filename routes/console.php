<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Queue 3-day and 24-hour event reminders. Hourly is granular enough to hit
// each window promptly while staying cheap; the command is idempotent.
Schedule::command('events:send-reminders')->hourly();
