<?php

namespace App\Http\Requests;

use App\Models\Tontine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TontineRequest extends FormRequest
{
    // L'autorisation est vérifiée AVANT la validation : un intrus reçoit 403
    public function authorize(): bool
    {
        $tontine = $this->route('tontine');

        return $tontine instanceof Tontine
            ? $this->user()->can('update', $tontine)
            : $this->user()->can('create', Tontine::class);
    }

    protected function prepareForValidation(): void
    {
        $fee = $this->input('organizer_fee_percent') ?: 0;

        // Sans la fonction "commission" dans le plan, la valeur existante est conservée et rien ne peut être imposé
        if ($this->user() && ! \App\Support\Entitlements::for($this->user())->has('organizer_fee')) {
            $tontine = $this->route('tontine');
            $fee = $tontine instanceof Tontine ? (float) $tontine->organizer_fee_percent : 0;
        }

        $this->merge(['organizer_fee_percent' => $fee]);
    }

    public function rules(): array
    {
        $name = ['required', 'string', 'max:255'];
        $tontine = $this->route('tontine');

        // Les règles d'argent sont figées dès que le calendrier existe : seul le nom reste modifiable
        if ($tontine instanceof Tontine && $tontine->isLocked()) {
            return ['name' => $name];
        }

        return [
            'name' => $name,
            'amount_per_period' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'organizer_fee_percent' => ['required', 'numeric', 'min:0', 'max:' . Tontine::MAX_FEE_PERCENT, 'decimal:0,2'],
            'period_type' => ['required', Rule::in(['weekly', 'monthly'])],
            'start_date' => ['required', 'date'],
        ];
    }
}
