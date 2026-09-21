<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use app\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Returns the authenticated user's notifications.
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    /**
     * POST /api/notifications
     * Creates a notification for the authenticated user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'type'    => 'nullable|string|in:info,success,warning,achievement',
        ]);

        $notification = Notification::create([
            'user_id' => $request->user()->id,
            'title'   => $validated['title'],
            'message' => $validated['message'],
            'type'    => $validated['type'] ?? 'info',
            'is_read' => false,
        ]);

        return response()->json([
            'message'      => 'Notification created.',
            'notification' => $notification,
        ], 201);
    }
}