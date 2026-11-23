<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', ['items' => Announcement::latest('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:300'],
            'type' => ['required', Rule::in(Announcement::TYPES)],
            'audience' => ['required', Rule::in(Announcement::AUDIENCES)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $a = Announcement::create($data + ['created_by' => $request->user()->id, 'is_active' => true]);
        ActivityLog::record('announcement_saved', $request->user(), ['title' => $a->title]);

        return back()->with('status', __('Anons la pibliye.'));
    }

    public function toggle(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_active' => ! $announcement->is_active]);
        ActivityLog::record('announcement_saved', $request->user(), ['title' => $announcement->title, 'active' => $announcement->is_active]);

        return back()->with('status', $announcement->is_active ? __('Anons la parèt.') : __('Anons la kache.'));
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        ActivityLog::record('announcement_saved', $request->user(), ['title' => $announcement->title, 'deleted' => true]);
        $announcement->delete();

        return back()->with('status', __('Anons la efase.'));
    }
}
