<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $baseUrl = $request->getBaseUrl();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(10)->get()->map(fn ($notification): array => [
                'message' => $notification->data['message'] ?? 'Ada pembaruan klaim.',
                'created_at' => $notification->created_at->locale('id')->diffForHumans(),
                'read' => $notification->read_at !== null,
                'read_url' => $baseUrl.route('notifications.read', $notification->id, false),
            ])->values(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if (is_string($url) && preg_match('#^/claims/[0-9]+$#D', $url) === 1) {
            return redirect($url);
        }

        return redirect()->route('dashboard');
    }
}
