<x-admin-layout active="overview" :title="__('Vi jeneral')">
    @php
        $cur = config('lajanansanm.currency');
        $money = fn ($v) => $v >= 1000000 ? number_format($v / 1000000, 2) . 'M' : number_format($v, 0, '.', ',');
        $trend = $kpi['new_users_trend'];
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4 mb-space-lg">
        <div>
            <h1 class="font-headline-lg text-headline-lg">{{ __('Vi jeneral konsòl la') }}</h1>
            <p class="text-on-surface-variant max-w-2xl">{{ __('Chif total sèlman. Nou pa montre non ni telefòn manm ki nan tontin yo.') }}</p>
        </div>
        <div class="flex bg-surface-container-high p-1 rounded-xl font-label-md text-label-md">
            @foreach ([7, 30, 90] as $d)
                <a href="{{ route('admin.overview', ['days' => $d]) }}" class="px-4 py-2 rounded-lg {{ $days === $d ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant' }}">{{ $d }} {{ __('jou') }}</a>
            @endforeach
        </div>
    </div>

    {{-- Indicateurs --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-space-md mb-space-lg">
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Itilizatè total') }}</p><p class="font-headline-md text-headline-md tabular-nums">{{ number_format($kpi['total_users']) }}</p></div>
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Nouvo itilizatè') }}</p>
            <p class="font-headline-md text-headline-md tabular-nums">{{ number_format($kpi['new_users']) }}</p>
            <p class="font-body-sm text-body-sm {{ $trend === null ? 'text-slate-400' : ($trend >= 0 ? 'text-emerald-600' : 'text-secondary') }}">
                {{ $trend === null ? '—' : ($trend >= 0 ? '▲ +' : '▼ ') . $trend . '%' }} · {{ $days }} {{ __('jou') }}</p></div>
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Itilizatè aktif') }}</p><p class="font-headline-md text-headline-md tabular-nums">{{ number_format($kpi['active_users']) }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">30 {{ __('jou') }}</p></div>
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Tontin aktif') }}</p><p class="font-headline-md text-headline-md tabular-nums">{{ number_format($kpi['active_tontines']) }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $kpi['completed_tontines'] }} {{ __('fini') }}</p></div>
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Volim kotizasyon') }}</p><p class="font-headline-md text-headline-md tabular-nums">{{ $money($kpi['volume_month']) }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $cur }} · {{ __('mwa sa a') }}</p></div>
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Vèsman mwa sa a') }}</p><p class="font-headline-md text-headline-md tabular-nums">{{ $money($kpi['payouts_month']) }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $cur }} · net</p></div>
    </div>

    {{-- Graphiques --}}
    <div class="grid lg:grid-cols-2 gap-space-md mb-space-lg">
        <div class="card space-y-space-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div><h2 class="font-title-lg text-title-lg">{{ __('Kwasans') }}</h2><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Nouvo itilizatè ak nouvo tontin') }}</p></div>
                <div class="flex gap-3 font-label-sm text-label-sm"><span class="flex items-center gap-1"><i class="w-3 h-3 rounded-full" style="background:#0c2382"></i>{{ __('Itilizatè') }}</span><span class="flex items-center gap-1"><i class="w-3 h-3 rounded-full" style="background:#10b981"></i>{{ __('Tontin') }}</span></div>
            </div>
            <x-admin.line-chart :labels="$charts['labels']" :series="[['name' => 'u', 'color' => '#0c2382', 'values' => $charts['signups']], ['name' => 't', 'color' => '#10b981', 'values' => $charts['tontines']]]" />
        </div>
        <div class="card space-y-space-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div><h2 class="font-title-lg text-title-lg">{{ __('Volim chak jou') }}</h2><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $days > 30 ? __('Pa semèn') : __('Pa jou') }} · {{ $cur }}</p></div>
                <div class="flex gap-3 font-label-sm text-label-sm"><span class="flex items-center gap-1"><i class="w-3 h-3 rounded-full" style="background:#0c2382"></i>{{ __('Kotizasyon') }}</span><span class="flex items-center gap-1"><i class="w-3 h-3 rounded-full" style="background:#10b981"></i>{{ __('Vèsman') }}</span></div>
            </div>
            <x-admin.bar-chart :labels="$charts['labels']" :series="[['name' => 'c', 'color' => '#0c2382', 'values' => $charts['contribs']], ['name' => 'p', 'color' => '#10b981', 'values' => $charts['payouts']]]" />
        </div>
    </div>

    {{-- Entonnoir --}}
    <div class="card mb-space-lg space-y-space-md">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div><h2 class="font-title-lg text-title-lg">{{ __('Antonwa aktivasyon') }}</h2><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Kantite itilizatè ki rive nan chak etap') }}</p></div>
            <span class="badge bg-primary-fixed text-primary-container">{{ __('To final') }} : {{ end($funnel)['pct'] }}%</span>
        </div>
        <div class="grid sm:grid-cols-3 xl:grid-cols-6 gap-space-sm">
            @foreach ($funnel as $i => $step)
                <div class="rounded-xl p-space-sm {{ $loop->last ? 'bg-primary-fixed' : 'bg-surface-container-low' }}">
                    <div class="flex justify-between font-label-sm text-label-sm text-on-surface-variant"><span>{{ $i + 1 }}. {{ __($step['label']) }}</span><span>{{ $step['pct'] }}%</span></div>
                    <p class="font-headline-md text-headline-md tabular-nums">{{ number_format($step['count']) }}</p>
                    <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden my-1"><div class="h-1.5 rounded-full bg-primary-container" style="width: {{ $step['pct'] }}%"></div></div>
                    <p class="font-label-sm text-label-sm {{ $step['drop'] ? 'text-secondary' : 'text-slate-400' }}">{{ $step['drop'] === null ? __('Pwen depa') : __('Gout') . ' : −' . $step['drop'] . '%' }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-space-md">
        <div class="card lg:col-span-2 space-y-space-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-title-lg text-title-lg">{{ __('Dènye aktivite') }}</h2>
                <a href="{{ route('admin.activity') }}" class="font-label-md text-label-md text-primary-container">{{ __('Wè tout jounal la') }} →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recent as $log)
                    <div class="flex items-center gap-3 py-2.5">
                        <div class="flex-1 min-w-0">
                            <p class="font-label-lg text-label-lg truncate">{{ $log->user?->name ?? __('Sistèm') }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $log->description() }}</p>
                        </div>
                        @include('admin._chip', ['log' => $log])
                        <span class="hidden sm:block w-24 text-right font-body-sm text-body-sm text-on-surface-variant">{{ $log->created_at?->locale(app()->getLocale())->diffForHumans(short: true) }}</span>
                    </div>
                @empty
                    <x-empty-state compact icon="history" :title="__('Pa gen aktivite ankò')" />
                @endforelse
            </div>
        </div>

        <div class="card space-y-space-sm">
            <h2 class="font-title-lg text-title-lg">{{ __('To reta global') }}</h2>
            <div class="rounded-xl p-space-md {{ $late['rate'] < 10 ? 'bg-emerald-50' : 'bg-error-container/50' }}">
                <p class="font-headline-lg text-headline-lg tabular-nums {{ $late['rate'] < 10 ? 'text-emerald-700' : 'text-secondary' }}">{{ $late['rate'] }}%</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $late['unpaid'] }} {{ __('peman an reta sou') }} {{ $late['expected'] }} {{ __('ki dwe fèt nan tou ouvè yo') }}</p>
            </div>
            @php $total = max(1, array_sum($late['buckets'])); @endphp
            @foreach ($late['buckets'] as $range => $count)
                <div>
                    <div class="flex justify-between font-label-md text-label-md"><span>{{ __('Reta') }} {{ $range }} {{ __('jou') }}</span><span class="tabular-nums">{{ $count }}</span></div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-2 rounded-full {{ $range === '8+' ? 'bg-secondary' : ($range === '4-7' ? 'bg-amber-400' : 'bg-primary-container') }}" style="width: {{ round($count / $total * 100) }}%"></div></div>
                </div>
            @endforeach
        </div>
    </div>
</x-admin-layout>
