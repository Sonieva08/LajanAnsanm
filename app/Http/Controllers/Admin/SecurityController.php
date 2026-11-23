<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function show(Request $request): View
    {
        $u = $request->user();

        return view('admin.settings.security', [
            'enabled' => (bool) $u->two_factor_confirmed_at,
            'pending' => $u->two_factor_secret && ! $u->two_factor_confirmed_at ? $u->two_factor_secret : null,
            'uri' => $u->two_factor_secret ? Totp::uri($u->two_factor_secret, $u->email) : null,
        ]);
    }

    public function setup(Request $request): RedirectResponse
    {
        $u = $request->user();
        abort_if($u->two_factor_confirmed_at, 409);

        $u->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => null])->save();

        return back()->with('status', __('Kle kreye. Mete l nan aplikasyon otantifikasyon ou an, epi konfime ak yon kòd.'));
    }

    public function confirm(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        abort_unless($u->two_factor_secret && ! $u->two_factor_confirmed_at, 404);

        if (! Totp::verify($u->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => __('Kòd la pa bon. Eseye ankò.')]);
        }

        $u->forceFill(['two_factor_confirmed_at' => now()])->save();
        $request->session()->put('admin_2fa_ok', true);
        ActivityLog::record('two_factor_on', $u);

        return back()->with('status', __('Verifikasyon 2 etap aktive.'));
    }

    public function disable(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);

        if (! $u->two_factor_confirmed_at || ! Totp::verify($u->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => __('Kòd la pa bon. Eseye ankò.')]);
        }

        $u->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        $request->session()->forget('admin_2fa_ok');
        ActivityLog::record('two_factor_off', $u);

        return back()->with('status', __('Verifikasyon 2 etap dezaktive.'));
    }

    public function verifyForm(): View
    {
        return view('admin.settings.challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);

        if (! $u->two_factor_confirmed_at) {
            return redirect()->route('admin.overview');
        }

        if (! Totp::verify($u->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => __('Kòd la pa bon. Eseye ankò.')]);
        }

        $request->session()->put('admin_2fa_ok', true);
        $to = $request->session()->pull('admin_2fa_intended');

        return redirect($to ?: route('admin.overview'));
    }
}
