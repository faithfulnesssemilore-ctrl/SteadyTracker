<?php

use App\ActivityStatus;
use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityStatusChanged;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Artisan;

it('stores and exposes user notifications from the database channel', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'title' => 'Review sprint plan',
        'description' => 'Check the roadmap before kickoff.',
        'activity_status' => ActivityStatus::Pending,
        'due_at' => now()->addDay(),
        'start_at' => now()->addHours(2),
    ]);

    $user->notify(new ActivityStatusChanged(
        $activity,
        'Up next',
        'Your sprint plan is scheduled for today.',
        ActivityStatus::Pending,
    ));

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
        'notifiable_type' => User::class,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonFragment([
            'title' => 'Up next',
            'status' => 'pending',
        ]);
});

it('creates a status notification when an activity is started', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'title' => 'Deep work block',
        'description' => 'Focus on the core feature and write tests.',
        'activity_status' => ActivityStatus::Pending,
        'priority' => 'medium',
        'due_at' => now()->addDay(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/activities/'.$activity->id.'/start')
        ->assertOk();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
        'notifiable_type' => User::class,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonFragment([
            'title' => 'Activity started',
            'status' => 'in_progress',
        ]);
});

it('broadcasts status notifications on the authenticated user channel', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $activity = Activity::create([
        'user_id' => $user->id,
        'title' => 'Plan tomorrow',
        'activity_status' => ActivityStatus::Pending,
    ]);
    $notification = new ActivityStatusChanged(
        $activity,
        'Task created',
        'Your task is ready to begin.',
        ActivityStatus::Pending,
    );

    expect($notification->broadcastAs())->toBe('NotificationCreated')
        ->and($notification->broadcastOn())->toEqual([new PrivateChannel('user.'.$user->id)])
        ->and($notification->toBroadcast($user)->data)->toMatchArray([
            'type' => 'activity_status',
            'status' => 'pending',
            'activity_id' => $activity->id,
        ]);
});

it('notifies once when an active activity becomes overdue', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $activity = Activity::create([
        'user_id' => $user->id,
        'title' => 'Submit report',
        'activity_status' => ActivityStatus::Pending,
        'due_at' => now()->subMinute(),
    ]);

    Artisan::call('activities:notify-overdue');
    Artisan::call('activities:notify-overdue');

    expect($user->notifications()->where('data->title', 'Task overdue')->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonFragment([
            'title' => 'Task overdue',
            'status' => 'pending',
        ]);
});
