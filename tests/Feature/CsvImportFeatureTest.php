<?php

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('imports valid rows and rejects duplicates for the same user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Activity::create([
        'user_id' => $user->id,
        'title' => 'Pay rent',
        'description' => 'Already exists',
        'priority' => 'high',
        'activity_status' => 'pending',
        'due_at' => '2026-09-30 17:00:00',
    ]);

    $csv = <<<'CSV'
title,description,priority,activity_status,due_at
Pay rent,Monthly rent,high,pending,2026-09-30 17:00:00
Book flight,Trip planning,low,pending,2026-10-12 07:30:00
Morning run,Quick workout,medium,pending,2026-10-05 06:00:00
CSV;

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/csv-import', [
            'file' => UploadedFile::fake()->createWithContent('activities.csv', $csv),
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('imported', 2)
        ->assertJsonPath('rejected', 1);

    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'title' => 'Pay rent', 'due_at' => '2026-09-30 17:00:00'])
        ->assertDatabaseHas('activities', ['user_id' => $user->id, 'title' => 'Book flight'])
        ->assertDatabaseHas('activities', ['user_id' => $user->id, 'title' => 'Morning run'])
        ->assertDatabaseCount('activities', 3);
});
