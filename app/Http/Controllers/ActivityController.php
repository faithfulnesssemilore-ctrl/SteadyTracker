<?php

namespace App\Http\Controllers;

use App\ActivityStatus;
use App\Events\ActivityCreated;
use App\Events\ActivityDeleted;
use App\Events\ActivityUpdated;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Notifications\ActivityStatusChanged;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $activities = $request->user()
            ->activities()
            ->orderBy('due_at')
            ->get();

        return ActivityResource::collection($activities);
    }// This is the ActivityController class, which handles various actions related to activities, such as listing, creating, updating, deleting, starting, and completing activities.
    // It also includes a private method to notify users of status changes in their activities. why becase

    public function store(StoreActivityRequest $request)
    {
        $activity = $request->user()->activities()->create(
            $request->validated() + ['activity_status' => ActivityStatus::Pending]
        );

        $this->notifyStatusChange($activity, ActivityStatus::Pending);

        broadcast(new ActivityCreated($activity))->toOthers();

        return new ActivityResource($activity);
    }

    public function update(StoreActivityRequest $request, Activity $activity)
    {
        $this->authorize('update', $activity);

        $activity->fill($request->validated());
        $activity->save();

        broadcast(new ActivityUpdated($activity))->toOthers();

        return new ActivityResource($activity);
    }

    public function destroy(Activity $activity)
    {
        $this->authorize('delete', $activity);

        broadcast(new ActivityDeleted($activity->id, $activity->user_id))->toOthers();

        $activity->delete();

        return response()->noContent();
    }

    public function start(Activity $activity)
    {
        $this->authorize('update', $activity);
        abort_unless(
            $activity->activity_status === ActivityStatus::Pending,
            422,
            'Only pending activities can be started.'
        );

        $activity->update([
            'activity_status' => ActivityStatus::InProgress,
            'start_at' => now(),
        ]);

        $this->notifyStatusChange($activity->fresh(), ActivityStatus::InProgress);

        broadcast(new ActivityUpdated($activity))->toOthers();

        return new ActivityResource($activity);
    }

    public function complete(Activity $activity)
    {
        $this->authorize('update', $activity);
        abort_unless(
            in_array($activity->activity_status, [ActivityStatus::Pending, ActivityStatus::InProgress], true),
            422,
            'Only pending or in-progress activities can be completed.'
        );

        $activity->update([
            'activity_status' => ActivityStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->notifyStatusChange($activity->fresh(), ActivityStatus::Completed);

        broadcast(new ActivityUpdated($activity))->toOthers();

        return new ActivityResource($activity);
    }

    private function notifyStatusChange(Activity $activity, ActivityStatus $status): void
    {
        $title = match ($status) {
            ActivityStatus::Pending => 'Task created',
            ActivityStatus::InProgress => 'Activity started',
            ActivityStatus::Completed => 'Activity completed',
        };

        $message = match ($status) {
            ActivityStatus::Pending => 'Your task “'.$activity->title.'” is ready to begin.',
            ActivityStatus::InProgress => 'Your task “'.$activity->title.'” is now in progress.',
            ActivityStatus::Completed => 'Your task “'.$activity->title.'” has been completed.',
        };

        $activity->user->notify(new ActivityStatusChanged(
            $activity,
            $title,
            $message,
            $status,
        ));
    }
}
