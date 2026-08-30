<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.notifications.index', [
            'notifications' => $request->user()
                ?->notifications()
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user
            ?->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? route('admin.notifications.index'),
                'level' => $notification->data['level'] ?? 'info',
                'icon' => $notification->data['icon'] ?? 'bi-bell',
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
                'diff_for_humans' => $notification->created_at?->diffForHumans(),
            ]) ?? collect();

        return response()->json([
            'unread_count' => $user?->unreadNotifications()->count() ?? 0,
            'latest_id' => data_get($notifications->first(), 'id'),
            'notifications' => $notifications->values(),
            'all_read_url' => route('admin.notifications.mark-all-read'),
            'index_url' => route('admin.notifications.index'),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $userNotification = $request->user()
            ?->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $userNotification->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Notifications marked as read.');
    }
}
