<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar la limpieza de tokens expirados cada hora
Schedule::call(function () {
    \Artisan::call('tokens:clean');
})->hourly();
