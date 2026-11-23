<x-admin-layout active="overview" :title="__('Verifikasyon')">
    <div class="max-w-md mx-auto card !p-space-lg space-y-space-md text-center">
        <span class="material-symbols-outlined text-[40px] text-primary-container">lock</span>
        <h1 class="font-headline-md text-headline-md">{{ __('Konfime se ou menm') }}</h1>
        <p class="text-on-surface-variant">{{ __('Mete kòd 6 chif aplikasyon otantifikasyon ou an pou louvri konsòl la.') }}</p>
        @if ($errors->any())<div class="alert-err">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.security.verify.submit') }}" class="space-y-space-sm">
            @csrf
            <input name="code" inputmode="numeric" autocomplete="one-time-code" class="input text-center tabular-nums text-[24px] tracking-[0.3em]" maxlength="10" required autofocus>
            <button class="btn-primary w-full">{{ __('Verifye') }}</button>
        </form>
    </div>
</x-admin-layout>
