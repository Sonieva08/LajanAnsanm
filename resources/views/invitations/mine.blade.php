<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface">{{ __('sòl mwen yo') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 flex flex-col gap-space-md">
            @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="alert-err">{{ $errors->first() }}</div>@endif

            @forelse ($memberships as $m)
                <div class="card !p-space-md flex items-center justify-between gap-space-md">
                    <div class="min-w-0">
                        <p class="font-title-lg text-title-lg truncate">{{ $m->tontine->name }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ number_format((float) $m->tontine->amount_per_period, 0, '.', ',') }} {{ config('lajanansanm.currency') }}
                            · {{ __('Pozisyon') }} {{ $m->position }}
                        </p>
                    </div>
                    @if ($m->tontine->status === 'completed')
                        <span class="badge-muted">{{ __('Sòl fèmen') }}</span>
                    @else
                        <a href="{{ route('memberships.portal', $m) }}" class="btn-primary">{{ __('Antre nan pòtay la') }}</a>
                    @endif
                </div>
            @empty
                <x-empty-state icon="groups" :title="__('Ou pa nan okenn sòl ankò')" :text="__('Lè yon òganizatè envite ou, lyen an ap ouvri isit la.')" />
            @endforelse
        </div>
    </div>
</x-app-layout>
