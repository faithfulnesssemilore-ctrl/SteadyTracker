<?php

namespace App\Notifications;

use App\ActivityStatus;
use App\Models\Activity;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ActivityStatusChanged extends Notification
{
    public function __construct(
        public Activity $activity,
        public string $title,
        public string $message,
        public ActivityStatus $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'id' => $this->activity->id,
            'type' => 'activity_status',
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status->value,
            'activity_id' => $this->activity->id,
            'due_at' => $this->activity->due_at?->toISOString(),
            'start_at' => $this->activity->start_at?->toISOString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->activity->id,
            'type' => 'activity_status',
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status->value,
            'activity_id' => $this->activity->id,
            'due_at' => $this->activity->due_at?->toISOString(),
            'start_at' => $this->activity->start_at?->toISOString(),
            'created_at' => now()->toISOString(),
        ]);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.'.$this->activity->user_id);
    }

    public function broadcastAs(): string
    {
        return 'NotificationCreated';
    }
}
