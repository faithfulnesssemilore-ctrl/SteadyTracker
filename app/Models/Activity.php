<?php

namespace App\Models;

use App\ActivityStatus;
use App\Priority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'activity_status',
        'priority',
        'due_at',
    ];

    protected $casts = [
        'activity_status' => ActivityStatus::class,
        'priority' => Priority::class,
        'due_at' => 'datetime',
        'start_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
