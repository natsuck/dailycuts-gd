<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:reconcile-maya')->everyFiveMinutes();
Schedule::command('orders:expire-unpaid')->hourly();
Schedule::command('orders:retry-lalamove-dispatch')->everyFifteenMinutes();
Schedule::command('orders:dispatch-pending')->everyFiveMinutes();
