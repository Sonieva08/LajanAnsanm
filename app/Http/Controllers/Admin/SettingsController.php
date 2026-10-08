<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings', ['maintenance' => Setting::maintenance()]);
    }

    public function maintenance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:300'],
            'ends_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        $enabled = $request->boolean('enabled');
        $was = Setting::maintenance()['enabled'];

        Setting::set('maintenance', [
            'enabled' => $enabled,
            'message' => $data['message'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'updated_by' => $request->user()->name,
            'updated_at' => now()->toIso8601String(),
        ]);

        if ($enabled !== $was) {
            ActivityLog::record($enabled ? 'maintenance_on' : 'maintenance_off', $request->user(), array_filter(['ends_at' => $data['ends_at'] ?? null]));
        }

        return back()->with('status', $enabled ? __('Mòd antretyen aktive.') : __('Mòd antretyen dezaktive.'));
    }
}
