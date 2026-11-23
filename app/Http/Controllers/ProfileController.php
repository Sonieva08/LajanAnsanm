<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use App\Notifications\EmailChangeAlert;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $emailChanged = mb_strtolower($data['email']) !== mb_strtolower($user->email);
        $user->name = $data['name'];

        if (! $emailChanged) {
            $user->save();

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }

        $pendingEmail = $data['email'];
        $user->forceFill(['pending_email' => $pendingEmail])->save();

        $url = URL::temporarySignedRoute(
            'profile.email.confirm',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => sha1($pendingEmail)]
        );

        Notification::route('mail', $pendingEmail)->notify(new ConfirmEmailChange($url));

        return Redirect::route('profile.edit')->with('status', 'email-change-pending');
    }

    public function resendEmailChange(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(blank($user->pending_email), 404);

        $url = URL::temporarySignedRoute(
            'profile.email.confirm',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => sha1($user->pending_email)]
        );

        Notification::route('mail', $user->pending_email)->notify(new ConfirmEmailChange($url));

        return Redirect::route('profile.edit')->with('status', 'email-change-link-sent');
    }

    public function confirmEmailChange(Request $request, User $user, string $hash): RedirectResponse
    {
        $result = DB::transaction(function () use ($user, $hash): array {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $pendingEmail = $lockedUser->pending_email;

            abort_if(blank($pendingEmail) || ! hash_equals(sha1($pendingEmail), $hash), 403);

            $emailInUse = User::query()
                ->whereKeyNot($lockedUser->id)
                ->whereRaw('lower(email) = ?', [mb_strtolower($pendingEmail)])
                ->exists();

            abort_if($emailInUse, 409);

            $oldEmail = $lockedUser->email;
            $lockedUser->forceFill([
                'email' => $pendingEmail,
                'pending_email' => null,
                'email_verified_at' => now(),
            ])->save();

            event(new Verified($lockedUser));

            return ['old_email' => $oldEmail, 'new_email' => $pendingEmail];
        });

        Notification::route('mail', $result['old_email'])
            ->notify(new EmailChangeAlert($result['new_email']));

        if ($request->user()?->is($user)) {
            return Redirect::route('profile.edit')->with('status', 'email-change-completed');
        }

        return Redirect::route('login')->with('status', 'email-change-completed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
