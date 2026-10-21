<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
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
