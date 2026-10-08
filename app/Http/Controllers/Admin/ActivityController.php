<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));
        $action = array_key_exists((string) $request->query('action'), ActivityLog::ACTIONS) ? $request->query('action') : null;
        $q = trim((string) $request->query('q', ''));

        $filtered = fn () => ActivityLog::query()
            ->when($from, fn ($b) => $b->where('created_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($b) => $b->where('created_at', '<=', $to->copy()->endOfDay()))
            ->when($q !== '', fn ($b) => $b->whereHas('user', fn ($u) => $u
                ->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")));

        if ($request->query('format') === 'csv') {
            return $this->csv($filtered()->when($action, fn ($b) => $b->where('action', $action))->with('user')->latest('id')->limit(5000)->get());
        }

        return view('admin.activity', [
            'logs' => $filtered()->when($action, fn ($b) => $b->where('action', $action))
                ->with('user')->latest('id')->paginate(25)->withQueryString(),
            'summary' => $filtered()->selectRaw('action, count(*) as c')->groupBy('action')->orderByDesc('c')->pluck('c', 'action'),
            'filters' => ['from' => $from?->format('Y-m-d'), 'to' => $to?->format('Y-m-d'), 'action' => $action, 'q' => $q],
        ]);
    }

    private function date(mixed $value): ?Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', (string) $value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function csv($logs): StreamedResponse
    {
        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Itilizatè', 'Imèl', 'Aksyon', 'Detay', 'IP']);

            foreach ($logs as $l) {
                $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;
                fputcsv($out, [
                    $l->created_at?->format('d/m/Y H:i:s'), $safe($l->user?->name), $safe($l->user?->email),
                    $l->label(), $l->description(), $l->ip,
                ]);
            }

            fclose($out);
        }, 'jounal-aktivite-' . today()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
