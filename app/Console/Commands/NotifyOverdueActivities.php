<?php

namespace App\Console\Commands;

use App\ActivityStatus;
use App\Models\Activity;
use App\Notifications\ActivityStatusChanged;
use Illuminate\Console\Command;

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
            ->each(function (Activity $activity): void {
                $alreadyNotified = $activity->user->notifications()
                    ->where('type', ActivityStatusChanged::class)
                    ->where('data->activity_id', $activity->id)
                    ->where('data->title', 'Task overdue')
                    ->exists();

                if ($alreadyNotified) {
                    return;
                }

                $activity->user->notifyNow(new ActivityStatusChanged(
                    $activity,
                    'Task overdue',
                    '"'.$activity->title.'" is overdue. It is still available to complete.',
                    $activity->activity_status,
                ));
            });

        return self::SUCCESS;
    }
}
