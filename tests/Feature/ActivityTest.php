<?php

use App\Models\Activity;
use App\Models\HabitCompletion;
use App\Models\User;
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
