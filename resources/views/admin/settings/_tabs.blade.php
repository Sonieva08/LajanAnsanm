@php
    $tabs = [
        ['maintenance', __('Mòd antretyen'), route('admin.settings'), 'construction'],
        ['security', __('Sekirite'), route('admin.settings.security'), 'lock'],
        ['payments', __('Peman'), route('admin.plans.payment'), 'account_balance_wallet'],
        ['notifications', __('Notifikasyon'), route('admin.settings.notifications'), 'notifications'],
    ];
@endphp
<div class="flex items-center gap-1 bg-surface-container-low p-1.5 rounded-xl overflow-x-auto mb-space-md">
    @foreach ($tabs as [$key, $label, $url, $icon])
        <a href="{{ $url }}" class="flex items-center gap-2 px-4 py-2 rounded-lg font-label-md text-label-md whitespace-nowrap
           {{ $active === $key ? 'bg-surface-container-lowest text-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-lowest' }}">
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>{{ $label }}
        </a>
    @endforeach
</div>
