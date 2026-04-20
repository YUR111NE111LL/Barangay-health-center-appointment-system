<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule plan expiration checks daily at 9:00 AM
Schedule::command('app:check-plan-expirations')
    ->dailyAt('09:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

Schedule::command('github:sync-releases')
    ->hourly()
    ->withoutOverlapping()
    ->when(fn (): bool => is_string(config('github.token')) && config('github.token') !== '');
