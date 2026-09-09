<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptionify:expire-overdue')
    ->hourly()
    ->withoutOverlapping(30)
    ->onOneServer();

Schedule::command('cuotas:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping(30)
    ->onOneServer();

Schedule::command('cuotas:refresh-exchange-rate')
    ->weekdays()
    ->dailyAt('18:00')
    ->withoutOverlapping(30)
    ->onOneServer();
