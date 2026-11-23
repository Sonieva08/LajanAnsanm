<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $email = mb_strtolower((string) $this->input('email'));
        $emailChanged = $email !== mb_strtolower($user->email);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', function ($attribute, $value, $fail) use ($user): void {
                $email = mb_strtolower((string) $value);
                $alreadyInUse = User::query()
                    ->whereKeyNot($user->id)
                    ->where(function ($query) use ($email): void {
                        $query->whereRaw('lower(email) = ?', [$email])
                            ->orWhereRaw('lower(pending_email) = ?', [$email]);
                    })
                    ->exists();

                if ($alreadyInUse) {
                    $fail(__('This email address is already in use or awaiting confirmation.'));
                }
            }],
            'current_password' => $emailChanged
                ? ['required', 'current_password']
                : ['nullable', 'current_password'],
        ];
    }
}
