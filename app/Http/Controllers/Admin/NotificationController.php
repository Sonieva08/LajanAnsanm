<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function show(): View
    {
        $template = Setting::reminderTemplate();

        return view('admin.settings.notifications', [
            'template' => $template,
            'sample' => Setting::renderReminder(['nom' => 'Marie', 'montan' => '500', 'cur' => config('lajanansanm.currency'), 'tontin' => 'Sòl Test', 'tou' => 3, 'dat' => '16/10/2026'], $template),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['template' => ['required', 'string', 'max:300']]);

        Setting::set('reminder_template', $data['template']);
        ActivityLog::record('reminder_template_updated', $request->user());

        return back()->with('status', __('Modèl rapèl la sove.'));
    }
}
