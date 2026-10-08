<x-app-layout>
    <x-page-header :back="route('tontines.index')" :title="__('Kreye yon tontine')" :subtitle="__('Mete enfòmasyon debaz yo pou kòmanse.')" />

    <form method="POST" action="{{ route('tontines.store') }}" class="card !p-space-lg space-y-space-lg">
        @include('tontines._form')
        <button class="btn-primary w-full"><span class="material-symbols-outlined">check_circle</span> {{ __('Kreye tontine sa a') }}</button>
    </form>
</x-app-layout>
