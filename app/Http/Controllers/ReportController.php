<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Cycle;
use App\Models\Tontine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Tontine $tontine): View
    {
        $this->authorize('view', $tontine);

        $rounds = $tontine->cycles()
            ->with('beneficiary')
            ->withCount('contributions')
            ->withSum('contributions', 'amount')
            ->get()
            ->groupBy('round')
            ->sortKeysDesc();

        return view('reports.index', compact('tontine', 'rounds'));
    }

    // Rapport d'un tour
    public function tour(Request $request, Tontine $tontine, Cycle $cycle): View|StreamedResponse
    {
        $this->authorize('view', $tontine);

        $data = $this->tourData($tontine, $cycle);

        if ($request->query('format') === 'csv') {
            return $this->tourCsv($tontine, $cycle, $data);
        }

        return view('reports.tour', $data + ['tontine' => $tontine, 'cycle' => $cycle]);
    }

    // Rapport d'un cycle complet (kòl)
    public function round(Request $request, Tontine $tontine, int $round): View|StreamedResponse
    {
        $this->authorize('view', $tontine);

        $data = $this->roundData($tontine, $round);

        if ($request->query('format') === 'csv') {
            return $this->roundCsv($tontine, $round, $data);
        }

        return view('reports.round', $data + ['tontine' => $tontine, 'round' => $round]);
    }

    /* ---------- Données ---------- */

    private function tourData(Tontine $tontine, Cycle $cycle): array
    {
        $cycle->load(['beneficiary', 'payout']);

        $contributions = $cycle->contributions()->get()->keyBy('member_id');

        // Membres actifs + membres retirés qui ont tout de même payé ce tour
        $members = $tontine->members()->get()
            ->filter(fn ($m) => $m->is_active || $contributions->has($m->id))
            ->sortBy(fn ($m) => $m->position ?? PHP_INT_MAX)
            ->values();

        $rows = $members->map(function ($m) use ($cycle, $contributions) {
            $paid = $contributions->get($m->id);

            $status = match (true) {
                (bool) $paid => 'paid',
                $cycle->status === 'pending' => 'upcoming',
                $cycle->status === 'closed' => 'missed',
                $cycle->isLate() => 'late',
                default => 'waiting',
            };

            return ['member' => $m, 'contribution' => $paid, 'status' => $status];
        });

        $expected = (float) $tontine->amount_per_period * $members->count();
        $collected = (float) $contributions->sum('amount');

        return [
            'rows' => $rows,
            'expected' => $expected,
            'collected' => $collected,
            'missing' => max(0, $expected - $collected),
            'paidCount' => $contributions->count(),
            'memberCount' => $members->count(),
            'payout' => $cycle->payout,
        ];
    }

    private function roundData(Tontine $tontine, int $round): array
    {
        $cycles = $tontine->cycles()
            ->where('round', $round)
            ->with(['beneficiary', 'payout'])
            ->withCount('contributions')
            ->withSum('contributions', 'amount')
            ->get();

        abort_if($cycles->isEmpty(), 404);

        $contribs = Contribution::whereIn('cycle_id', $cycles->pluck('id'))->get()->groupBy('member_id');

        $involved = $contribs->keys()
            ->merge($cycles->pluck('beneficiary_member_id')->filter())
            ->unique();

        $members = $tontine->members()->get()
            ->filter(fn ($m) => $m->is_active || $involved->contains($m->id))
            ->sortBy(fn ($m) => $m->position ?? PHP_INT_MAX)
            ->values();

        // Tours "dus" : clôturés, ou ouverts mais en retard
        $due = $cycles->filter(fn ($c) => $c->status === 'closed' || $c->isLate());

        $received = $cycles->filter(fn ($c) => $c->payout)
            ->groupBy(fn ($c) => $c->payout->member_id)
            ->map(fn ($g) => $g->sum(fn ($c) => $c->payout->netAmount()));

        $amount = (float) $tontine->amount_per_period;

        $memberRows = $members->map(function ($m) use ($contribs, $due, $received, $amount) {
            $mine = $contribs->get($m->id, collect());
            $paidCycleIds = $mine->pluck('cycle_id');
            $missed = $due->filter(fn ($c) => ! $paidCycleIds->contains($c->id))->count();

            return [
                'member' => $m,
                'paid_count' => $mine->count(),
                'paid_sum' => (float) $mine->sum('amount'),
                'missed' => $missed,
                'owed' => $missed * $amount,
                'received' => (float) ($received->get($m->id) ?? 0),
            ];
        });

        return [
            'cycles' => $cycles,
            'memberRows' => $memberRows,
            'totals' => [
                'collected' => (float) $cycles->sum('contributions_sum_amount'),
                'paid_out' => (float) $cycles->sum(fn ($c) => (float) ($c->payout?->netAmount() ?? 0)),
                'fees' => (float) $cycles->sum(fn ($c) => (float) ($c->payout?->fee_amount ?? 0)),
                'owed' => (float) $memberRows->sum('owed'),
                'closed' => $cycles->where('status', 'closed')->count(),
                'count' => $cycles->count(),
            ],
        ];
    }

    /* ---------- CSV ---------- */

    private function tourCsv(Tontine $tontine, Cycle $cycle, array $d): StreamedResponse
    {
        $name = 'rapo-' . Str::slug($tontine->name) . "-kol{$cycle->round}-tou{$cycle->cycle_number}.csv";

        return $this->csv($name, function ($out) use ($tontine, $cycle, $d) {
            fputcsv($out, [__('Tontin'), $this->safe($tontine->name)]);
            fputcsv($out, [__('Kòl'), $cycle->round]);
            fputcsv($out, [__('Tou'), $cycle->cycle_number]);
            fputcsv($out, [__('Benefisyè'), $this->safe($cycle->beneficiary?->name)]);
            fputcsv($out, [__('Dat limit'), $cycle->deadline->format('d/m/Y')]);
            fputcsv($out, [__('Lajan atann'), $d['expected']]);
            fputcsv($out, [__('Ranmase'), $d['collected']]);
            fputcsv($out, [__('Manke'), $d['missing']]);
            fputcsv($out, [__('Kòmisyon òganizatè'), (float) ($d['payout']?->fee_amount ?? 0)]);
            fputcsv($out, [__('Vèse'), $d['payout']?->netAmount() ?? 0]);
            fputcsv($out, []);
            fputcsv($out, ['#', __('Non'), __('Estati'), __('Metòd peman'), __('Dat'), __('Kantite'), __('Nòt (opsyonèl)')]);

            foreach ($d['rows'] as $r) {
                $c = $r['contribution'];
                fputcsv($out, [
                    $r['member']->position,
                    $this->safe($r['member']->name),
                    $this->statusLabel($r['status']),
                    $c ? $this->methodLabel($c->payment_method) : '',
                    $c?->paid_at->format('d/m/Y') ?? '',
                    $c?->amount ?? 0,
                    $this->safe($c?->note),
                ]);
            }
        });
    }

    private function roundCsv(Tontine $tontine, int $round, array $d): StreamedResponse
    {
        $name = 'rapo-' . Str::slug($tontine->name) . "-kol{$round}.csv";

        return $this->csv($name, function ($out) use ($tontine, $round, $d) {
            fputcsv($out, [__('Tontin'), $this->safe($tontine->name)]);
            fputcsv($out, [__('Kòl'), $round]);
            fputcsv($out, [__('Total ranmase'), $d['totals']['collected']]);
            fputcsv($out, [__('Total kòmisyon'), $d['totals']['fees']]);
            fputcsv($out, [__('Total vèse'), $d['totals']['paid_out']]);
            fputcsv($out, [__('Total dwe'), $d['totals']['owed']]);
            fputcsv($out, []);

            fputcsv($out, [__('Tou'), __('Benefisyè'), __('Dat limit'), __('Peye'), __('Ranmase'), __('Vèse'), __('Dat vèsman'), __('Estati')]);
            foreach ($d['cycles'] as $c) {
                fputcsv($out, [
                    $c->cycle_number,
                    $this->safe($c->beneficiary?->name),
                    $c->deadline->format('d/m/Y'),
                    $c->contributions_count,
                    (float) ($c->contributions_sum_amount ?? 0),
                    (float) ($c->payout?->netAmount() ?? 0),
                    $c->payout?->paid_at?->format('d/m/Y') ?? '',
                    $c->status,
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['#', __('Non'), __('Tou peye'), __('Total peye'), __('Tou manke'), __('Lajan dwe'), __('Lajan resevwa')]);
            foreach ($d['memberRows'] as $r) {
                fputcsv($out, [
                    $r['member']->position,
                    $this->safe($r['member']->name),
                    $r['paid_count'],
                    $r['paid_sum'],
                    $r['missed'],
                    $r['owed'],
                    $r['received'],
                ]);
            }
        });
    }

    private function csv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 : Excel lit bien les accents
            $writer($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Empêche l'injection de formules quand le CSV est ouvert dans Excel
    private function safe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    private function statusLabel(string $s): string
    {
        return match ($s) {
            'paid' => __('Peye'),
            'missed' => __('Pa peye'),
            'late' => __('An reta'),
            'waiting' => __('Ap tann'),
            default => __('Ap vini'),
        };
    }

    private function methodLabel(?string $m): string
    {
        return match ($m) {
            'cash' => __('Lajan kach'),
            'moncash' => 'MonCash',
            'natcash' => 'NatCash',
            'bank' => __('Bank'),
            default => (string) $m,
        };
    }
}
