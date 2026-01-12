<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'type',
        'scheduled_at',
        'scheduled_time',
        'day_of_week',
        'day_of_month',
        'frequency',
        'is_completed',
        'completed_at',
        'image_path',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];
}
