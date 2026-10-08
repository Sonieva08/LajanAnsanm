<x-error-layout code="500" icon="build" tone="red" :title="__('Gen yon pwoblèm sou sèvè a')" :message="__('Se pa fòt ou. Done ou yo an sekirite.') . ' ' . __('Eseye ankò nan kèk minit.')">
    <x-slot:pill>
        <div class="inline-flex items-center gap-1.5 bg-secondary-fixed text-on-secondary-fixed px-space-sm py-0.5 rounded-full font-label-sm text-label-sm">
            <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
            {{ __('Referans') }} : SRV-{{ now()->format('ymd-His') }}
        </div>
    </x-slot:pill>
    <button type="button" onclick="location.assign(location.href)" class="btn-primary flex-1"><span class="material-symbols-outlined">refresh</span> {{ __('Eseye ankò') }}</button>
    <a href="{{ url('/') }}" class="btn-secondary flex-1 !bg-surface-container-low"><span class="material-symbols-outlined">home</span> {{ __('Paj dakèy') }}</a>
</x-error-layout>
