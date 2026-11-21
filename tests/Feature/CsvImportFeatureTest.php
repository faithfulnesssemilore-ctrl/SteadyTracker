<?php

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityCsvImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Mockery\MockInterface;

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

it('rejects CSV uploads larger than 100 MB', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/csv-import', [
            'file' => UploadedFile::fake()->create('oversized.csv', 102401, 'text/csv'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('does not expose internal CSV import exception messages', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->mock(ActivityCsvImportService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('import')->andThrow(new RuntimeException('SECRET_DB_DETAIL'));
    });

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/csv-import', [
            'file' => UploadedFile::fake()->createWithContent(
                'activities.csv',
                "title,description,priority,activity_status,due_at\nBook flight,,low,pending,\n",
            ),
        ])
        ->assertStatus(422)
        ->assertJsonMissing(['message' => 'SECRET_DB_DETAIL'])
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['request_id']);
});

it('throttles repeated CSV imports for the same user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    RateLimiter::clear(($user->id).'|127.0.0.1');

    $file = fn () => UploadedFile::fake()->createWithContent(
        'activities.csv',
        "title,description,priority,activity_status,due_at\nBook flight,,low,pending,\n",
    );

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/csv-import', ['file' => $file()])
            ->assertOk();
    }

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/csv-import', ['file' => $file()])
        ->assertTooManyRequests();
});
