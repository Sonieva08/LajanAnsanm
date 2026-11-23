<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function preferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s\-]*$/'],
            'locale' => ['required', Rule::in(['ht', 'fr'])],
        ]);

        $user = $request->user();
        $user->forceFill(['phone' => $data['phone'] ?: null, 'locale' => $data['locale']])->save();
        $request->session()->put('locale', $data['locale']);

        return back()->with('status', __('Preferans sove.'));
    }

    public function export(Request $request): JsonResponse|Response
    {
        $user = $request->user();

        if ($request->query('format') === 'csv') {
            return $this->csv($user);
        }

        return response()->json([
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'locale' => $user->locale,
                'created_at' => $user->created_at?->toIso8601String(),
                'google_linked' => (bool) $user->google_id,
                'has_password' => filled($user->password),
            ],
            'subscriptions' => $user->subscriptions()->with('plan:id,name')->get()->map(fn ($subscription) => [
                'plan' => $subscription->plan?->name,
                'status' => $subscription->status,
                'period' => $subscription->billing_period,
                'starts_at' => $subscription->starts_at?->toDateString(),
                'ends_at' => $subscription->ends_at?->toDateString(),
                'amount' => (float) $subscription->amount,
            ])->values(),
            'subscription_requests' => $user->subscriptionRequests()->with('plan:id,name')->get()->map(fn ($subscriptionRequest) => [
                'plan' => $subscriptionRequest->plan?->name,
                'reference' => $subscriptionRequest->reference_code,
                'amount' => (float) $subscriptionRequest->amount,
                'status' => $subscriptionRequest->status,
                'created_at' => $subscriptionRequest->created_at?->toDateString(),
            ])->values(),
            'tontines' => $user->tontines()->with('members:id,tontine_id,name,phone,email,position,is_active,invited_at,invitation_accepted_at')->get()->map(fn ($tontine) => [
                'name' => $tontine->name,
                'amount_per_period' => (float) $tontine->amount_per_period,
                'period_type' => $tontine->period_type,
                'status' => $tontine->status,
                'start_date' => $tontine->start_date?->toDateString(),
                'members' => $tontine->members->map(fn ($member) => [
                    'name' => $member->name,
                    'phone' => $member->phone,
                    'email' => $member->email,
                    'position' => $member->position,
                    'active' => $member->is_active,
                    'invited_at' => $member->invited_at?->toIso8601String(),
                    'invitation_accepted_at' => $member->invitation_accepted_at?->toIso8601String(),
                ])->values(),
            ])->values(),
        ], 200, ['Content-Disposition' => 'attachment; filename="done-lajanansanm.json"']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (self::runningTontines($user) > 0) {
            return back()->withErrors(['account' => __('Ou gen sòl an kou. Fini yo oswa mete yo an pòz anvan ou efase kont ou.')]);
        }

        if (filled($user->password)) {
            $request->validate(['password' => ['required', 'current_password']]);
        } else {
            $request->validate(
                ['confirm' => ['required', Rule::in(['EFASE'])]],
                ['confirm.in' => __('Konfimasyon an pa bon.')]
            );
        }

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public static function runningTontines(User $user): int
    {
        return $user->tontines()->whereIn('status', ['active', 'paused'])->count();
    }

    private function csv(User $user): Response
    {
        $rows = [['tontine', 'statut', 'montant', 'période', 'membre', 'téléphone', 'e-mail', 'position', 'actif']];

        foreach ($user->tontines()->with('members')->get() as $tontine) {
            $base = [$tontine->name, $tontine->status, (float) $tontine->amount_per_period, $tontine->period_type];

            if ($tontine->members->isEmpty()) {
                $rows[] = [...$base, '', '', '', '', ''];
            }

            foreach ($tontine->members as $member) {
                $rows[] = [...$base, $member->name, $member->phone, $member->email, $member->position, $member->is_active ? 'wi' : 'non'];
            }
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, array_map([$this, 'safeCsvCell'], $row));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="done-lajanansanm.csv"',
        ]);
    }

    private function safeCsvCell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
