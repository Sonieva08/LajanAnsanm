{{-- À inclure dans la page des membres de la tontine (réservé à l'organisateur) --}}
<section class="card !p-space-lg space-y-space-md">
    <div>
        <h3 class="font-title-lg text-title-lg">{{ __('Envite yon moun ki gen kont LajanAnsanm') }}</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Moun nan ap bezwen konekte pou aksepte. Apre sa, li ka jwenn pòtay li nan paj sòl pa li yo.') }}</p>
    </div>

    @if (session('invite_url'))
        <div class="rounded-xl bg-surface-container-low p-space-md space-y-space-sm">
            <p class="font-label-md text-label-md">{{ __('Lyen envitasyon an (montre yon sèl fwa):') }}</p>
            <p class="font-mono text-body-sm break-all select-all">{{ session('invite_url') }}</p>
            <div class="flex flex-col sm:flex-row gap-space-sm">
                <a href="{{ session('invite_wa') }}" target="_blank" rel="noopener noreferrer" class="btn-primary flex-1">{{ __('Voye sou WhatsApp') }}</a>
                <a href="{{ session('invite_sms') }}" class="btn-secondary flex-1">{{ __('Voye pa SMS') }}</a>
            </div>
        </div>
    @endif

    @if ($errors->has('invite'))<div class="alert-err">{{ $errors->first('invite') }}</div>@endif

    <form method="POST" action="{{ route('invitations.store', $tontine) }}" class="space-y-space-sm">
        @csrf
        <div>
            <label class="label" for="account-invitation-phone">{{ __('Nimewo WhatsApp') }}</label>
            <input id="account-invitation-phone" name="phone" type="tel" required class="input w-full" value="{{ old('phone') }}" placeholder="+509 ...">
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <label class="flex items-start gap-2 font-body-sm text-body-sm text-on-surface">
            <input type="checkbox" name="contact_permission" value="1" required class="mt-1 rounded border-slate-300 text-primary-container">
            <span>{{ __('Mwen gen pèmisyon pou m sèvi ak nimewo sa a pou voye envitasyon an.') }}</span>
        </label>
        <x-input-error :messages="$errors->get('contact_permission')" />
        <button class="btn-secondary w-full">{{ __('Kreye envitasyon') }}</button>
    </form>
</section>
