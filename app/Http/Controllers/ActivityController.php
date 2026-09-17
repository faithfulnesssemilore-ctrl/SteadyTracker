<?php

namespace App\Http\Controllers;

use App\Events\ActivityCreated;
use App\Events\ActivityUpdated;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $activities = $request->user()
            ->activities()
            ->with('category')
            ->orderBy('due_at')
            ->get();

        return ActivityResource::collection($activities);
    }

    public function store(StoreActivityRequest $request)
    {
        $activity = $request->user()->activities()->create(
            $request->validated() + ['activity_status' => 'pending']
        );

        broadcast(new ActivityCreated($activity))->toOthers();

        return new ActivityResource($activity->load('category'));
    }

    public function start(Activity $activity)
    {
        $this->authorize('update', $activity);

        $activity->update([
            'activity_status' => 'in_progress',
            'start_at' => now(),
        ]);

        broadcast(new ActivityUpdated($activity))->toOthers();

        return new ActivityResource($activity->load('category'));
    }

    public function complete(Activity $activity)
    {
        $this->authorize('update', $activity);

        $activity->update([
            'activity_status' => 'completed',
            'completed_at' => now(),
        ]);

        if ($activity->is_habit) {
            $activity->habitCompletions()->firstOrCreate([
                'completed_on' => Carbon::today(),
            ]);
        }

        broadcast(new ActivityUpdated($activity))->toOthers();

        return new ActivityResource($activity->load('category'));
    }
}
