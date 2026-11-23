<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

// Permet à un compte créé avec Google de définir un mot de passe (ou de le changer)
class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.password-set', ['hasPassword' => filled($request->user()->password)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = ['password' => ['required', 'confirmed', Password::defaults()]];
        if (filled($user->password)) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);
        $user->forceFill(['password' => $data['password']])->save();

        return back()->with('status', __('Modpas la sove.'));
    }
}
