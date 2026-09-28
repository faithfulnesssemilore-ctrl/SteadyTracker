<?php

use App\ActivityStatus;
use App\Models\User;

it('creates activities with descriptions and updates their lifecycle status', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/activities', [
            'title' => 'Write project notes',
            'description' => 'Capture the decisions from today\'s planning session.',
            'priority' => 'high',
            'due_at' => now()->addHour()->toISOString(),
        ])
        ->assertCreated();

    $activityId = $response->json('id');

    $response->assertJsonPath('description', 'Capture the decisions from today\'s planning session.')
        ->assertJsonPath('activity_status', ActivityStatus::Pending->value);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/activities/'.$activityId.'/start')
        ->assertOk()
        ->assertJsonPath('activity_status', ActivityStatus::InProgress->value);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/activities/'.$activityId.'/complete')
        ->assertOk()
        ->assertJsonPath('activity_status', ActivityStatus::Completed->value);
});

it('rejects invalid activity status transitions', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $activity = $user->activities()->create([
        'title' => 'Completed task',
        'activity_status' => ActivityStatus::Completed,
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/activities/'.$activity->id.'/start')
        ->assertUnprocessable();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/activities/'.$activity->id.'/complete')
        ->assertUnprocessable();

    $this->assertDatabaseHas('activities', [
        'id' => $activity->id,
        'activity_status' => ActivityStatus::Completed->value,
    ]);
});
