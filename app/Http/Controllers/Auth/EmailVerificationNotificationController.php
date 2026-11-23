<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Verified;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Illuminate\Validation\ValidationException;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Email verification notification could not be sent.', [
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception::class,
            ]);

            return back()->with('status', 'verification-email-failed');
        }

            return back()->with('status', 'verification-code-sent');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if (! app(\App\Services\EmailVerificationCodeService::class)->verify($user, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => __('The verification code is invalid or expired.'),
            ]);
        }

        if ($user->markEmailAsVerified()) {
            Event::dispatch(new Verified($user));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
