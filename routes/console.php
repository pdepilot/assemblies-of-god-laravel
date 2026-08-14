<?php

use App\Console\Commands\TransferMinistryByAgeCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(TransferMinistryByAgeCommand::class)->daily()->at('01:15');
