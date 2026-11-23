<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')->withErrors(['email' => __('Koneksyon Google poko disponib.')]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['email' => __('Koneksyon Google echwe. Eseye ankò.')]);
        }

        $email = mb_strtolower((string) $google->getEmail());
        $raw = (array) $google->getRaw();
        $verified = (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? false);

        // On ne lie jamais un compte à une adresse que Google ne confirme pas
        if ($email === '' || ! $verified) {
            return redirect()->route('login')->withErrors(['email' => __('Adrès imèl Google sa a pa verifye.')]);
        }

        $byGoogle = User::where('google_id', $google->getId())->first();
        $byEmail = User::where('email', $email)->first();

        if ($byGoogle) {
            $user = $byGoogle;
        } elseif ($byEmail) {
            if ($byEmail->google_id) {
                return redirect()->route('login')->withErrors(['email' => __('Kont sa a gen yon lòt kont Google ki lye ak li.')]);
            }

            // Même adresse, confirmée par Google : on lie le compte existant (le mot de passe reste valable)
            $byEmail->forceFill([
                'google_id' => $google->getId(),
                'google_linked_at' => now(),
                'email_verified_at' => $byEmail->email_verified_at ?? now(),
            ])->save();
            $user = $byEmail;
        } else {
            $user = new User();
            $user->forceFill([
                'name' => $google->getName() ?: $email,
                'email' => $email,
                'password' => null,
                'google_id' => $google->getId(),
                'google_linked_at' => now(),
                'email_verified_at' => now(),
            ])->save();
            ActivityLog::record('register', $user);
            app(\App\Services\TrialService::class)->grant($user);
        }

        if ($user->disabled_at) {
            return redirect()->route('login')->withErrors(['email' => __('Kont sa a dezaktive. Kontakte sipò a.')]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
