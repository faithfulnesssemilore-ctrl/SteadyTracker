<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Activity $activity) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.'.$this->activity->user_id);
    }

    public function broadcastAs(): string
    {
        return 'ActivityUpdated';
    }

    public function broadcastWith(): array
    {
        return (new ActivityResource($this->activity->load('category')))->resolve();
    }
}
