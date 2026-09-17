<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->where(function ($q) use ($request) {
                $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
            })
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Notification $notification)
    {
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request)
    {
        Notification::where(function ($q) use ($request) {
                $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
            })
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}