<x-portal-layout :title="__('Envitasyon pou antre nan sòl la')">
    <section class="card !p-space-lg space-y-space-md">
        <div class="text-center space-y-space-sm">
            <span class="material-symbols-outlined text-[42px] text-primary">groups</span>
            <h1 class="font-headline-lg-mobile text-headline-lg-mobile">{{ __('Yo envite w antre nan :tontine', ['tontine' => $member->tontine->name]) }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">
                {{ __(':organizer envite w vin manm nan sòl la.', ['organizer' => $member->tontine->admin->name]) }}
            </p>
        </div>

        <div class="rounded-xl bg-surface-container-low p-space-md text-center">
            <p class="font-title-md text-title-md">{{ $member->name }}</p>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Aksepte pou ouvri pòtay pèsonèl manm ou an.') }}</p>
        </div>

        @if ($errors->has('consent'))
            <div class="alert-err">{{ $errors->first('consent') }}</div>
        @endif

        <form method="POST" action="{{ route('member.invitation.accept', $token) }}" class="space-y-space-md">
            @csrf
            <label class="flex items-start gap-space-sm font-body-sm text-body-sm text-on-surface">
                <input type="checkbox" name="consent" value="1" required class="mt-1 rounded border-slate-300 text-primary-container">
                <span>
                    {{ __('Mwen dakò pou yo itilize non ak kontak mwen pou jere sòl sa a.') }}
                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="underline text-primary-container">{{ __('Politik konfidansyalite') }}</a>
                </span>
            </label>
            <button class="btn-primary w-full">{{ __('Aksepte envitasyon an epi ouvri pòtay mwen') }}</button>
        </form>

        <p class="text-center font-body-sm text-body-sm text-on-surface-variant">
            {{ __('Kenbe lyen pèsonèl sa a an sekirite. Li bay aksè ak pòtay sòl ou an.') }}
        </p>
    </section>
</x-portal-layout>
