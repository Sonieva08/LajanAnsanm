<x-admin-layout active="help" :title="__('Kesyon moun poze')">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-space-lg">
        <div>
            <h1 class="font-headline-lg text-headline-lg">{{ __('Kesyon moun poze nan èd la') }}</h1>
            <p class="text-on-surface-variant max-w-2xl">{{ __('Sa ki pa gen repons montre ekran ou dwe senplifye oswa repons ou dwe ajoute. Pa gen non ni nimewo : tèks yo netwaye.') }}</p>
        </div>
        <div class="flex bg-surface-container-high p-1 rounded-xl font-label-md text-label-md">
            @foreach ([7, 30, 90] as $d)
                <a href="{{ route('admin.help', ['days' => $d]) }}" class="px-4 py-2 rounded-lg {{ $days === $d ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant' }}">{{ $d }} {{ __('jou') }}</a>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-space-md">
        <div class="card !p-0 overflow-hidden">
            <div class="p-space-md border-b border-slate-100"><h2 class="font-title-lg text-title-lg">{{ __('Kesyon san repons') }}</h2><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Sa moun chèche epi nou pa t gen repons pou li.') }}</p></div>
            @forelse ($misses as $m)
                <div class="flex items-center justify-between gap-3 px-space-md py-3 border-b border-slate-50">
                    <div class="min-w-0"><p class="font-label-lg text-label-lg truncate">{{ $m->q }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $m->audience === 'member' ? __('Manm') : __('Òganizatè') }}</p></div>
                    <span class="badge-late tabular-nums">{{ $m->c }}</span>
                </div>
            @empty
                <div class="p-space-md"><x-empty-state compact icon="forum" :title="__('Pa gen kesyon san repons')" :text="__('Bon siy : kesyon yo jwenn repons.')" /></div>
            @endforelse
        </div>

        <div class="card !p-0 overflow-hidden">
            <div class="p-space-md border-b border-slate-100"><h2 class="font-title-lg text-title-lg">{{ __('Repons ki pi konsilte') }}</h2><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $total }} {{ __('ouvèti pannen èd la') }}</p></div>
            @forelse ($opens as $o)
                <div class="flex items-center justify-between gap-3 px-space-md py-3 border-b border-slate-50">
                    <div class="min-w-0"><p class="font-label-lg text-label-lg truncate">{{ $o->question }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $o->audience === 'member' ? __('Manm') : __('Òganizatè') }}</p></div>
                    <span class="badge-info tabular-nums">{{ $o->c }}</span>
                </div>
            @empty
                <div class="p-space-md"><x-empty-state compact icon="help" :title="__('Pa gen done ankò')" /></div>
            @endforelse
        </div>
    </div>
</x-admin-layout>
