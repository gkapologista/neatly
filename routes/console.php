<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


use Illuminate\Support\Facades\Schedule;
use App\Models\Task;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Task::where('type', 'daily')->update(['is_completed' => false, 'completed_at' => null]);
})->daily();

Schedule::call(function () {
    Task::where('type', 'weekly')->update(['is_completed' => false, 'completed_at' => null]);
})->weekly();

Schedule::call(function () {
    Task::where('type', 'monthly')->update(['is_completed' => false, 'completed_at' => null]);
})->monthly();
