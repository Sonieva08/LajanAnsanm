<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Cycle;
use App\Models\PaymentDeclaration;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

// Côté créateur : il ne fait que VALIDER ce que les membres déclarent (il vérifie dans son MonCash / NatCash / banque)
class DeclarationController extends Controller
{
    public function index(Tontine $tontine): View
    {
        $this->authorize('view', $tontine);

        $base = fn () => $tontine->declarations()->with(['member:id,name', 'cycle:id,cycle_number,tontine_id']);

        return view('tontines.declarations', [
            'tontine' => $tontine,
            'pending' => $base()->where('payment_declarations.status', 'pending')->orderBy('payment_declarations.id')->get(),
            'recent' => $base()->whereIn('payment_declarations.status', ['approved', 'rejected'])->latest('payment_declarations.id')->limit(10)->get(),
        ]);
    }

    public function approve(Tontine $tontine, PaymentDeclaration $declaration): RedirectResponse
    {
        $this->authorize('update', $tontine);
        $this->ensureBelongs($tontine, $declaration);

        $result = DB::transaction(function () use ($tontine, $declaration) {
            $cycle = Cycle::whereKey($declaration->cycle_id)->lockForUpdate()->firstOrFail();
            $d = PaymentDeclaration::whereKey($declaration->id)->lockForUpdate()->first();

            if ($d->status !== 'pending') {
                return ['error' => __('Deklarasyon sa a deja trete.')];
            }

            if ($cycle->status !== 'open' || $cycle->id !== $d->cycle_id) {
                return ['error' => __('Tou sa a pa ouvè ankò. Pa ka valide peman an.')];
            }

            if (! $d->member->is_active) {
                return ['error' => __('Manm sa a pa aktif ankò.')];
            }

            // Le montant vient toujours de la tontine, pas de la déclaration
            $contribution = $cycle->contributions()->where('member_id', $d->member_id)->first()
                ?? $cycle->contributions()->create([
                    'member_id' => $d->member_id,
                    'amount' => $tontine->amount_per_period,
                    'paid_at' => today()->toDateString(),
                    'payment_method' => $d->payment_method,
                    'note' => filled($d->transaction_ref) ? 'Ref: '.$d->transaction_ref : null,
                ]);

            $d->update(['status' => 'approved', 'reviewed_at' => now(), 'contribution_id' => $contribution->id]);

            return ['ok' => __('Peman valide.')];
        });

        return $this->done($tontine, $result);
    }

    public function reject(Request $request, Tontine $tontine, PaymentDeclaration $declaration): RedirectResponse
    {
        $this->authorize('update', $tontine);
        $this->ensureBelongs($tontine, $declaration);

        $data = $request->validate(['review_note' => ['required', 'string', 'max:300']]);

        $result = DB::transaction(function () use ($declaration, $data) {
            $d = PaymentDeclaration::whereKey($declaration->id)->lockForUpdate()->first();

            if ($d->status !== 'pending') {
                return ['error' => __('Deklarasyon sa a deja trete.')];
            }

            $d->update(['status' => 'rejected', 'reviewed_at' => now(), 'review_note' => trim($data['review_note'])]);

            return ['ok' => __('Deklarasyon an rejte.')];
        });

        return $this->done($tontine, $result);
    }

    public function receipt(Tontine $tontine, PaymentDeclaration $declaration)
    {
        $this->authorize('view', $tontine);
        $this->ensureBelongs($tontine, $declaration);

        abort_unless($declaration->receipt_path && Storage::disk('local')->exists($declaration->receipt_path), 404);

        return Storage::disk('local')->response($declaration->receipt_path);
    }

    private function ensureBelongs(Tontine $tontine, PaymentDeclaration $declaration): void
    {
        abort_unless($declaration->cycle()->where('tontine_id', $tontine->id)->exists(), 404);
    }

    private function done(Tontine $tontine, array $result): RedirectResponse
    {
        $back = redirect()->route('tontines.declarations.index', $tontine);

        return isset($result['error']) ? $back->withErrors(['declaration' => $result['error']]) : $back->with('status', $result['ok']);
    }
}
