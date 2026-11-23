<x-admin-layout active="settings" :title="__('Sekirite ak aksè')">
    <div class="mb-space-lg">
        <h1 class="font-headline-lg text-headline-lg text-primary">{{ __('Sekirite ak aksè') }}</h1>
        <p class="text-on-surface-variant">{{ __('Pwoteje kont pwopriyetè a ak yon kòd ki chanje chak 30 segond.') }}</p>
    </div>
    @include('admin.settings._tabs', ['active' => 'security'])

    @if (session('status'))<div class="alert-ok mb-space-md">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert-err mb-space-md">{{ $errors->first() }}</div>@endif

    <div class="card max-w-2xl space-y-space-md">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="font-title-lg text-title-lg">{{ __('Verifikasyon 2 etap') }}</h2>
                <p class="text-on-surface-variant">{{ __('Aplikasyon tankou Google Authenticator oswa Microsoft Authenticator bay yon kòd 6 chif.') }}</p>
            </div>
            <span class="{{ $enabled ? 'badge-paid' : 'badge-muted' }}">{{ $enabled ? __('Aktif') : __('Inaktif') }}</span>
        </div>

        @if ($enabled)
            <form method="POST" action="{{ route('admin.settings.security.disable') }}" class="space-y-space-sm">
                @csrf
                <label class="label" for="code">{{ __('Kòd aktyèl pou dezaktive') }}</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="input tabular-nums" maxlength="10" required>
                <button class="btn-danger">{{ __('Dezaktive verifikasyon 2 etap') }}</button>
            </form>
        @elseif ($pending)
            <div class="space-y-space-sm">
                <p>{{ __('Antre kle sa a nan aplikasyon an, oswa louvri lyen an sou telefòn ou:') }}</p>
                <p class="font-mono bg-surface-container-low p-3 rounded-xl break-all select-all">{{ $pending }}</p>
                <a href="{{ $uri }}" class="font-label-md text-label-md text-primary-container underline">{{ __('Louvri nan aplikasyon otantifikasyon') }}</a>
            </div>
            <form method="POST" action="{{ route('admin.settings.security.confirm') }}" class="space-y-space-sm">
                @csrf
                <label class="label" for="code">{{ __('Mete kòd 6 chif aplikasyon an bay') }}</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="input tabular-nums" maxlength="10" required>
                <button class="btn-primary">{{ __('Konfime epi aktive') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.settings.security.setup') }}">
                @csrf
                <button class="btn-primary">{{ __('Kòmanse konfigirasyon') }}</button>
            </form>
        @endif
    </div>
</x-admin-layout>
