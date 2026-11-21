<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(50)
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

    public function markRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_unless(
            $notification->notifiable_type === $request->user()::class
            && (string) $notification->notifiable_id === (string) $request->user()->id,
            403,
        );

        $notification->markAsRead();

        return response()->json([
            'id' => $notification->id,
            'read_at' => $notification->read_at?->toISOString(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['marked_read' => $count]);
    }

    public function destroy(Request $request, DatabaseNotification $notification): Response
    {
        abort_unless(
            $notification->notifiable_type === $request->user()::class
            && (string) $notification->notifiable_id === (string) $request->user()->id,
            403,
        );

        $notification->delete();

        return response()->noContent();
    }
}
