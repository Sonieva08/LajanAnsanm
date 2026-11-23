<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpEvent;
use App\Support\HelpCenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function index(Request $request): View
    {
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;
        $since = now()->subDays($days);

        $misses = HelpEvent::where('type', 'miss')->where('created_at', '>=', $since)->whereNotNull('query')
            ->selectRaw('lower(query) as q, audience, count(*) as c')->groupBy('q', 'audience')->orderByDesc('c')->limit(25)->get();

        $opens = HelpEvent::whereIn('type', ['open', 'search'])->where('created_at', '>=', $since)->whereNotNull('faq_id')
            ->selectRaw('faq_id, audience, count(*) as c')->groupBy('faq_id', 'audience')->orderByDesc('c')->limit(15)->get()
            ->map(function ($row) {
                $row->question = HelpCenter::find($row->audience, $row->faq_id)['q'] ?? $row->faq_id;

                return $row;
            });

        return view('admin.help', [
            'days' => $days,
            'misses' => $misses,
            'opens' => $opens,
            'total' => HelpEvent::where('type', 'open')->whereNull('faq_id')->where('created_at', '>=', $since)->count(),
        ]);
    }
}
