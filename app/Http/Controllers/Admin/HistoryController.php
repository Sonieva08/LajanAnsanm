<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

// Historique des réglages : plans, règles, abonnements, maintenance, anons, sécurité, rappels
class HistoryController extends Controller
{
    public const ACTIONS = [
        'plan_updated', 'rules_updated', 'subscription_granted', 'subscription_cancelled', 'subscription_activated',
        'subscription_rejected', 'maintenance_on', 'maintenance_off', 'announcement_saved',
        'two_factor_on', 'two_factor_off', 'reminder_template_updated',
    ];

    public function index(): View
    {
        return view('admin.history', [
            'logs' => ActivityLog::with('user')->whereIn('action', self::ACTIONS)->latest('id')->paginate(30),
        ]);
    }
}
