<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        $query = $filter === 'unread'
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->latest()->paginate(15)->withQueryString();
        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'currentFilter' => $filter,
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = app()->getLocale();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($n) use ($locale) {
                return [
                    'id' => $n->id,
                    'title' => $locale === 'fr' ? ($n->data['title_fr'] ?? $n->data['title'] ?? 'Notification') : ($n->data['title'] ?? 'Notifikasyon'),
                    'message' => $locale === 'fr' ? ($n->data['message_fr'] ?? $n->data['message'] ?? '') : ($n->data['message'] ?? ''),
                    'action_url' => $n->data['action_url'] ?? route('notifications.index'),
                    'icon' => $n->data['icon'] ?? 'notifications',
                    'time_ago' => $n->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function read(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        if ($request->boolean('redirect') && ! empty($notification->data['action_url'])) {
            return redirect($notification->data['action_url']);
        }

        return back()->with('status', __('Notifikasyon make kòm li.'));
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', __('Tout notifikasyon yo make kòm li.'));
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', __('Notifikasyon an efase.'));
    }

    public function clearRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->readNotifications()->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', __('Tout notifikasyon ki te deja li yo efase.'));
    }
}
