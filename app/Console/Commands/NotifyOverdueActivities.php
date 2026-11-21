<?php

namespace App\Console\Commands;

use App\ActivityStatus;
use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityStatusChanged;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class NotifyOverdueActivities extends Command
{
    protected $signature = 'activities:notify-overdue';

    protected $description = 'Notify users about active activities that have passed their due time';

    public function handle(): int
    {
        Activity::query()
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->whereIn('activity_status', [ActivityStatus::Pending->value, ActivityStatus::InProgress->value])
            ->with('user')
            ->chunkById(100, function ($activities): void {
                $userIds = $activities->pluck('user_id')->unique()->values()->all();

                $existingKeys = DatabaseNotification::query()
                    ->where('notifiable_type', User::class)
                    ->whereIn('notifiable_id', $userIds)
                    ->where('type', ActivityStatusChanged::class)
                    ->where('data->title', 'Task overdue')
                    ->get(['notifiable_id', 'data'])
                    ->mapWithKeys(function (DatabaseNotification $notification): array {
                        $data = $notification->data;

                        return [(string) $notification->notifiable_id.'|'.($data['activity_id'] ?? '') => true];
                    });

                foreach ($activities as $activity) {
                    $key = (string) $activity->user_id.'|'.$activity->id;

                    if ($existingKeys->has($key)) {
                        continue;
                    }

                    $activity->user->notifyNow(new ActivityStatusChanged(
                        $activity,
                        'Task overdue',
                        '"'.$activity->title.'" is overdue. It is still available to complete.',
                        $activity->activity_status,
                    ));
                }
            });

        return self::SUCCESS;
    }
}
