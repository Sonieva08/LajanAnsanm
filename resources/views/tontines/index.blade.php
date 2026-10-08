<x-app-layout>
    <x-page-header :title="__('Tontin mwen yo')">
        <a href="{{ route('tontines.create') }}" class="btn-primary btn-sm"><span class="material-symbols-outlined text-[20px]">add</span> {{ __('Nouvo') }}</a>
    </x-page-header>

    @if (session('status'))<div class="alert-ok mb-space-md">{{ session('status') }}</div>@endif

    <div class="space-y-space-md">
        @forelse ($tontines as $tontine)
            @php
                $badge = match ($tontine->status) {
                    'active' => ['badge-paid', __('An kou')],
                    'completed' => ['badge-muted', __('Fini')],
                    'paused' => ['badge-wait', __('Sispann')],
                    default => ['badge-info', __('Poko kòmanse')],
                };
            @endphp
            <a href="{{ route('tontines.show', $tontine) }}" class="card block hover:shadow-md transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="{{ $badge[0] }}">{{ $badge[1] }}</span>
                        <h3 class="font-title-lg text-title-lg mt-1.5 truncate">{{ $tontine->name }}</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ $tontine->period_type === 'weekly' ? __('Chak semèn') : __('Chak mwa') }} · {{ $tontine->members_count }} {{ __('manm') }}
                        </p>
                    </div>
                    <div class="text-right shrink-0 tabular-nums">
                        <span class="font-title-md text-title-md text-primary-container block">{{ number_format($tontine->amount_per_period, 0, '.', ',') }} {{ config('lajanansanm.currency') }}</span>
                        <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
                    </div>
                </div>
            </a>
        @empty
            <x-empty-state art="tontine" :title="__('Ou pa gen tontin ankò')" :text="__('Kòmanse premye tontin kominotè w la kounye a pou w jere kotizasyon ak tiraj manm yo an sekirite.')"
                :href="route('tontines.create')" :action="__('Kreye premye tontin ou')" />
        @endforelse
    </div>
</x-app-layout>
