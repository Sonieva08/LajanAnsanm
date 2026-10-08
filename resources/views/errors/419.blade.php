<x-error-layout code="419" icon="history_toggle_off" :title="__('Paj la ekspire')" :message="__('Sesyon ou an rete san aktivite pandan yon ti tan pou pwoteje sekirite kont ou.')">
    <button type="button" onclick="location.assign(location.href)" class="btn-primary flex-1"><span class="material-symbols-outlined">refresh</span> {{ __('Rechaje paj la') }}</button>
    <a href="{{ url('/dashboard') }}" class="btn-secondary flex-1 !bg-surface-container-low"><span class="material-symbols-outlined">home</span> {{ __('Retounen sou Akèy') }}</a>
</x-error-layout>
