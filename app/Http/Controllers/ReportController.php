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
        $currency = config('lajanansanm.currency');
        $cycleStatus = match ($cycle->status) {
            'pending' => __('Ap vini'),
            'open' => __('Ouvè'),
            default => __('Fèmen'),
        };

        return $this->csv($name, function ($out) use ($tontine, $cycle, $d, $currency, $cycleStatus) {
            // ── En-tête (modèle : logo + nom + titre du rapport) ──
            fputcsv($out, ['LajanAnsanm', __('Rapport de tontine')]);
            fputcsv($out, [$this->safe($tontine->name)]);
            fputcsv($out, [__('Cycle') . ' ' . $cycle->round . ' · ' . __('Tour') . ' ' . $cycle->cycle_number . ' · ' . $cycleStatus]);
            fputcsv($out, []);

            // ── Cartes de résumé (modèle : 2 lignes × 3 paires clé/valeur) ──
            fputcsv($out, [
                __('Benefisyè'), $this->safe($cycle->beneficiary?->name),
                __('Dat limit'), $cycle->deadline->format('d/m/Y'),
                __('Peye'), $d['paidCount'] . ' / ' . $d['memberCount'],
                '',
            ]);
            fputcsv($out, [
                __('Lajan atann') . " ({$currency})", $d['expected'],
                __('Ranmase') . " ({$currency})", $d['collected'],
                __('Manke') . " ({$currency})", $d['missing'],
                '',
            ]);
            fputcsv($out, []);

            // ── Tableau détaillé par membre ──
            fputcsv($out, [__('Detay pa manm')]);
            fputcsv($out, [
                '#', __('Non'), __('Estati'), __('Metòd peman'), __('Dat'), __('Kantite') . " ({$currency})", __('Nòt (opsyonèl)'),
            ]);

            foreach ($d['rows'] as $r) {
                $c = $r['contribution'];
                fputcsv($out, [
                    $r['member']->position ?? '',
                    $this->safe($r['member']->name),
                    $this->statusLabel($r['status']),
                    $c ? $this->methodLabel($c->payment_method) : '',
                    $c?->paid_at->format('d/m/Y') ?? '',
                    $c?->amount ?? 0,
                    $this->safe($c?->note),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, [__('Ranmase total') . " ({$currency})", $d['collected']]);
            fputcsv($out, [__('Dokiman jenere pa LajanAnsanm')]);
        });
    }
        private function roundCsv(Tontine $tontine, int $round, array $d): StreamedResponse
    {
        $name = 'rapo-' . Str::slug($tontine->name) . "-kol{$round}.csv";

        $currency = config('lajanansanm.currency');

        $first = $d['cycles']->first()->deadline;
        $last = $d['cycles']->last()->deadline;

        $cycleStatus = match ($d['cycles']->first()->status) {
            'pending' => __('Ap vini'),
            'open' => __('Ouvè'),
            default => __('Fèmen'),
        };

        // Agrégats sur tous les membres du round
        $totalExpected = 0;
        $totalCollected = 0;
        $totalMissing = 0;
        $totalPaidCount = 0;
        $totalTourCount = 0;
        foreach ($d['memberRows'] as $r) {
            $totalExpected += (float) $r['paid_sum'] + (float) $r['owed'];
            $totalCollected += (float) $r['paid_sum'];
            $totalMissing += (float) $r['owed'];
            $totalPaidCount += (int) $r['paid_count'];
            $totalTourCount += (int) $r['paid_count'] + (int) $r['missed'];
        }

        return $this->csv($name, function ($out) use ($tontine, $round, $d, $currency, $first, $last, $cycleStatus, $totalExpected, $totalCollected, $totalMissing, $totalPaidCount, $totalTourCount) {
            // ── En-tête (modèle : logo + nom + titre du rapport) ──
            fputcsv($out, ['LajanAnsanm', __('Rapport de tontine')]);
            fputcsv($out, [$this->safe($tontine->name)]);
            fputcsv($out, [__('Cycle') . ' ' . $round . ' · ' . $first->format('d/m/Y') . ' → ' . $last->format('d/m/Y') . ' · ' . $cycleStatus]);
            fputcsv($out, []);

            // ── Cartes de résumé (modèle : 2 lignes × 3 paires clé/valeur) ──
            fputcsv($out, [
                __('Benefisyè'), $this->safe($d['cycles']->first()->beneficiary?->name),
                __('Dat limit'), $first->format('d/m/Y') . ' → ' . $last->format('d/m/Y'),
                __('Peye'), $totalPaidCount . ' / ' . $totalTourCount,
                '',
            ]);
            fputcsv($out, [
                __('Lajan atann') . " ({$currency})", $totalExpected,
                __('Ranmase') . " ({$currency})", $totalCollected,
                __('Manke') . " ({$currency})", $totalMissing,
                '',
            ]);
            fputcsv($out, []);

            // ── Tableau détaillé par membre ──
            fputcsv($out, [__('Detay pa manm')]);
            fputcsv($out, [
                '#', __('Non'), __('Statut'), __('Tours payés'), __('Total payé') . " ({$currency})", __('Tours manqués'), __('Dû') . " ({$currency})", __('Reçu') . " ({$currency})",
            ]);

            foreach ($d['memberRows'] as $r) {
                $memberStatus = (int) $r['paid_count'] >= (int) ($r['paid_count'] + $r['missed']) ? __('Peye') : __('An reta');
                fputcsv($out, [
                    $r['member']->position ?? '',
                    $this->safe($r['member']->name . ($r['member']->is_active ? '' : ' (' . __('retire') . ')')),
                    $memberStatus,
                    (int) $r['paid_count'],
                    (float) $r['paid_sum'],
                    (int) $r['missed'],
                    (float) $r['owed'],
                    (float) $r['received'],
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, [__('Ranmase total') . " ({$currency})", $totalCollected]);
            fputcsv($out, [__('Dokiman jenere pa LajanAnsanm')]);
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
