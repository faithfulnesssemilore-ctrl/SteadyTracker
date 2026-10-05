<?php

use App\ActivityStatus;
use App\Models\Activity;
use App\Models\User;
use App\Priority;

it('gets activity status and priority as enums while persisting string values', function () {
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

it('allows a verified user to update and delete their own activities', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'title' => 'Write sprint notes',
        'description' => 'Draft notes before kickoff.',
        'activity_status' => ActivityStatus::Pending,
        'priority' => Priority::Medium,
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/activities/'.$activity->id, [
            'title' => 'Write sprint retrospective',
            'description' => 'Capture wins and blockers.',
            'priority' => 'high',
        ])
        ->assertOk()
        ->assertJsonPath('title', 'Write sprint retrospective')
        ->assertJsonPath('priority', 'high');

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/activities/'.$activity->id)
        ->assertNoContent();

    $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
});
