@auth
    @if (auth()->user()->is_owner && ($maintenanceState = \App\Models\Setting::maintenance())['enabled'])
        <div class="mb-space-md flex items-center justify-between gap-2 bg-secondary-container text-on-secondary rounded-xl px-4 py-2.5">
            <div class="flex items-center gap-2 min-w-0">
                <span class="material-symbols-outlined text-[20px] shrink-0" style="font-variation-settings: 'FILL' 1;">warning</span>
                <span class="font-label-md text-label-md truncate">{{ __('Mòd antretyen aktif : sèlman ou menm ka wè sit la.') }}</span>
            </div>
            <a href="{{ route('admin.settings') }}" class="font-label-sm text-label-sm uppercase bg-white/20 px-2.5 py-0.5 rounded-full shrink-0">{{ __('Jere') }}</a>
        </div>
    @endif
@endauth
