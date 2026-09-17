<?php

use App\ActivityStatus;
use App\Models\Activity;
use App\Models\HabitCompletion;
use App\Models\User;
use App\Priority;
use Illuminate\Support\Carbon;

function createActivity(): Activity
{
    return Activity::create([
        'user_id' => User::factory()->create()->id,
        'title' => 'Daily habit',
    ]);
}

function createCompletion(Activity $activity, Carbon $date): HabitCompletion
{
    return HabitCompletion::create([
        'activity_id' => $activity->id,
        'completed_on' => $date,
    ]);
}

it('counts consecutive completions through today', function () {
    $activity = createActivity();

    createCompletion($activity, Carbon::today());
    createCompletion($activity, Carbon::yesterday());

    expect($activity->currentStreak())->toBe(2);
});

it('counts from yesterday when today is incomplete', function () {
    $activity = createActivity();

    createCompletion($activity, Carbon::yesterday());

    expect($activity->currentStreak())->toBe(1);
});

it('stops at the first missing day', function () {
    $activity = createActivity();

    createCompletion($activity, Carbon::today());
    createCompletion($activity, Carbon::today()->subDays(2));

    expect($activity->currentStreak())->toBe(1);
});

it('casts activity status and priority to enums while persisting string values', function () {
    $activity = Activity::create([
        'user_id' => User::factory()->create()->id,
        'title' => 'Plan sprint',
        'activity_status' => ActivityStatus::Pending,
        'priority' => Priority::High,
    ]);

    expect($activity->activity_status)->toBe(ActivityStatus::Pending)
        ->and($activity->priority)->toBe(Priority::High)
        ->and($activity->getRawOriginal('activity_status'))->toBe('pending')
        ->and($activity->getRawOriginal('priority'))->toBe('high');
});
