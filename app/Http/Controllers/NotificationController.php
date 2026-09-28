<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];

                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $data['title'] ?? 'New update',
                    'message' => $data['message'] ?? '',
                    'status' => $data['status'] ?? null,
                    'activity_id' => $data['activity_id'] ?? null,
                    'due_at' => $data['due_at'] ?? null,
                    'start_at' => $data['start_at'] ?? null,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at?->toISOString(),
                ];
            });

        return response()->json($notifications);
    }
}
