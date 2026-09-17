<?php

namespace App\Models;

use App\ActivityStatus;
use App\Priority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Activity extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category_id',
        'activity_status',
        'priority',
        'due_at',
        'is_habit',
    ];

    protected $casts = [
        'activity_status' => ActivityStatus::class,
        'priority' => Priority::class,
        'due_at' => 'datetime',
        'start_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_habit' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function habitCompletions(): HasMany
    {
        return $this->hasMany(HabitCompletion::class);
    }

    public function currentStreak(): int
    {
        $streak = 0;
        $date = Carbon::today();

        if (! $this->habitCompletions()->whereDate('completed_on', $date)->exists()) {
            $date = $date->subDay();
        }

        while ($this->habitCompletions()->whereDate('completed_on', $date)->exists()) {
            $streak++;
            $date = $date->subDay();
        }

        return $streak;
    }
}
