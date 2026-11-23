<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

// Côté créateur : ouvrir ou fermer l'espace membre, saisir les coordonnées de paiement, partager les liens
class PortalSettingsController extends Controller
{
    public function edit(Tontine $tontine): View
    {
        $this->authorize('view', $tontine);

        return view('tontines.portal', [
            'tontine' => $tontine,
            'members' => $tontine->members()->where('is_active', true)->get(),
            'payment' => $tontine->portalPayment(),
        ]);
    }

    public function update(Request $request, Tontine $tontine): RedirectResponse
    {
        if ($request->boolean('member_portal_enabled') && ! $tontine->member_portal_enabled && ! $tontine->is_legacy
            && ! \App\Support\Entitlements::for($request->user())->has('member_portal')) {
            return redirect()->route('plans')->with('limit', ['need' => 'member_portal', 'used' => null, 'max' => null]);
        }

        $this->authorize('update', $tontine);

        if ($tontine->member_portal_enabled && ! $request->boolean('member_portal_enabled')
            && $tontine->roundCycles()->whereDoesntHave(
                'payout',
                fn ($query) => $query->where('member_response', 'received')
            )->exists()) {
            return back()->withErrors([
                'member_portal_enabled' => __('Ou pa ka fèmen pòtay la anvan tout benefisyè yo konfime yo resevwa lajan yo.'),
            ]);
        }

        $data = $request->validate([
            'member_portal_enabled' => ['nullable', 'boolean'],
            'moncash_number' => ['nullable', 'string', 'max:40'],
            'moncash_name' => ['nullable', 'string', 'max:100'],
            'natcash_number' => ['nullable', 'string', 'max:40'],
            'natcash_name' => ['nullable', 'string', 'max:100'],
            'bank_info' => ['nullable', 'string', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:500'],
            'moncash_qr' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'natcash_qr' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_moncash_qr' => ['nullable', 'boolean'],
            'remove_natcash_qr' => ['nullable', 'boolean'],
        ]);

        $payment = $tontine->portalPayment();
        foreach (['moncash_number', 'moncash_name', 'natcash_number', 'natcash_name', 'bank_info', 'instructions'] as $key) {
            $payment[$key] = filled($data[$key] ?? null) ? trim($data[$key]) : null;
        }

        foreach (['moncash', 'natcash'] as $method) {
            $pathKey = $method.'_qr_path';
            $fileKey = $method.'_qr';
            $removeKey = 'remove_'.$fileKey;

            if ($request->hasFile($fileKey)) {
                $newPath = $request->file($fileKey)->store('tontine-payment-qrs', 'local');
                if (! $newPath) {
                    throw new \RuntimeException('Unable to store uploaded payment QR image.');
                }
                if ($payment[$pathKey]) {
                    Storage::disk('local')->delete($payment[$pathKey]);
                }
                $payment[$pathKey] = $newPath;
            } elseif ($request->boolean($removeKey)) {
                if ($payment[$pathKey]) {
                    Storage::disk('local')->delete($payment[$pathKey]);
                }
                $payment[$pathKey] = null;
            }
        }

        $tontine->update([
            'member_portal_enabled' => $request->boolean('member_portal_enabled'),
            'portal_payment' => $payment,
        ]);

        // À l'ouverture, chaque membre actif reçoit son lien
        if ($tontine->member_portal_enabled) {
            $tontine->members()->where('is_active', true)->whereNull('access_token_hash')->get()->each->issueAccessToken();
        }

        return redirect()->route('tontines.portal.edit', $tontine)->with('status', __('Reglaj pòtay la sove.'));
    }

    public function link(Tontine $tontine, Member $member): RedirectResponse
    {
        $this->authorize('update', $tontine);
        abort_unless($tontine->member_portal_enabled && $member->is_active, 404);

        $member->issueAccessToken();

        return redirect()->route('tontines.portal.edit', $tontine)->with('status', __('Nouvo lyen kreye. Ansyen lyen an pa mache ankò.'));
    }

    public function links(Tontine $tontine): RedirectResponse
    {
        $this->authorize('update', $tontine);
        abort_unless($tontine->member_portal_enabled, 404);

        $tontine->members()->where('is_active', true)->whereNull('access_token_hash')->get()->each->issueAccessToken();

        return redirect()->route('tontines.portal.edit', $tontine)->with('status', __('Lyen yo kreye.'));
    }
}
