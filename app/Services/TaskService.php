<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;

class TaskService
{
    public function createDefaultTasks(User $user)
    {
        $dailyTasks = [
            'Make bed',
            'Wash dishes',
            'Wipe kitchen counters',
            '15-min declutter',
            'Sweep high-traffic floors'
        ];

        $weeklyTasks = [
            'Deep clean bathrooms',
            'Vacuum/mop all floors',
            'Dust surfaces',
            'Launder bed linens',
            'Empty all trash bins'
        ];

        $monthlyTasks = [
            'Deep clean oven/fridge',
            'Wipe baseboards & window sills',
            'Wash windows',
            'Vacuum upholstery'
        ];

        foreach ($dailyTasks as $title) {
            $user->tasks()->create([
                'title' => $title,
                'type' => 'daily',
            ]);
        }

        foreach ($weeklyTasks as $title) {
            $user->tasks()->create([
                'title' => $title,
                'type' => 'weekly',
            ]);
        }

        foreach ($monthlyTasks as $title) {
            $user->tasks()->create([
                'title' => $title,
                'type' => 'monthly',
            ]);
        }
    }
}
