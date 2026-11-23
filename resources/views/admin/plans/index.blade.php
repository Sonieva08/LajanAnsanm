<x-admin-layout active="plans" :title="__('Plan ak abònman')">
    @php $cur = config('lajanansanm.currency'); @endphp

    <div class="flex flex-wrap items-start justify-between gap-4 mb-space-lg">
        <div>
            <h1 class="font-headline-lg text-headline-lg">{{ __('Jesyon plan ak abònman') }}</h1>
            <p class="text-on-surface-variant max-w-2xl">{{ __('Konfigire tarif yo, limit yo ak fonksyon yo. Pa gen kòd pou modifye.') }}</p>
        </div>
        <a href="{{ route('admin.plans.create') }}" class="btn-primary"><span class="material-symbols-outlined">add</span> {{ __('Nouvo plan') }}</a>
    </div>

    <div class="flex items-center gap-1 bg-surface-container-low p-1.5 rounded-xl mb-space-md overflow-x-auto">
        <span class="flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-container-lowest text-primary font-label-md text-label-md shadow-sm whitespace-nowrap"><span class="material-symbols-outlined text-[18px]">tune</span> {{ __('Lis plan yo') }}</span>
        <a href="{{ route('admin.plans.payment') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-lowest font-label-md text-label-md whitespace-nowrap"><span class="material-symbols-outlined text-[18px]">account_balance_wallet</span> {{ __('Kote kliyan yo voye peman an ak règ') }}</a>
        <a href="{{ route('admin.subscriptions') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-lowest font-label-md text-label-md whitespace-nowrap"><span class="material-symbols-outlined text-[18px]">card_membership</span> {{ __('Lis abònman yo') }}</a>
        <span class="flex items-center gap-2 px-4 py-2 rounded-lg text-slate-400 font-label-md text-label-md whitespace-nowrap cursor-not-allowed"><span class="material-symbols-outlined text-[18px]">history</span> {{ __('Istorik chanjman') }} <span class="badge-muted !py-0 !px-2 text-[10px]">{{ __('Byento') }}</span></span>
    </div>

    @if ($errors->has('plan'))<div class="alert-err mb-space-md">{{ $errors->first('plan') }}</div>@endif

    <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-space-md mb-space-lg">
        @foreach ($plans as $plan)
            <div class="card !p-space-md flex flex-col gap-space-sm {{ $plan->is_highlighted ? 'ring-2 ring-primary-container' : '' }}" data-plan-card="{{ $plan->slug }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        @if ($plan->badge)<span class="badge bg-primary-container text-on-primary mb-1">{{ $plan->badge }}</span>@endif
                        <h2 class="font-title-lg text-title-lg">{{ $plan->name }}</h2>
                    </div>
                    @if ($plan->is_free)
                        <span class="badge-muted">{{ __('Debaz') }}</span>
                    @else
                        <form method="POST" action="{{ route('admin.plans.toggle', $plan) }}">@csrf
                            <button class="badge {{ $plan->is_active ? 'badge-paid' : 'badge-muted' }}" title="{{ __('Chanje estati') }}">{{ $plan->is_active ? __('Aktif') : __('Inaktif') }}</button>
                        </form>
                    @endif
                </div>

                <p class="font-currency-display text-currency-display tabular-nums">
                    @if ($plan->is_free) 0 @elseif ($plan->price_monthly !== null) {{ number_format((float) $plan->price_monthly, 0, '.', ',') }} @else <span class="text-slate-400">—</span> @endif
                    <span class="font-title-md text-title-md text-on-surface-variant">{{ $cur }} / {{ __('mwa') }}</span>
                </p>
                @if (! $plan->is_free && $plan->price_monthly === null)
                    <p class="font-body-sm text-body-sm text-amber-700">{{ __('Fikse pri a anvan ou aktive plan sa a.') }}</p>
                @endif

                <ul class="font-body-md text-body-md space-y-1 text-on-surface-variant">
                    <li>{{ $plan->max_active_tontines ?? __('Ilimite') }} {{ __('sòl aktif') }}</li>
                    <li>{{ __('Jiska') }} {{ $plan->max_members_per_tontine ?? '∞' }} {{ __('manm pa sòl') }}</li>
                    <li>{{ count($plan->features ?? []) }} / {{ count(\App\Support\Features::ALL) }} {{ __('fonksyon') }}</li>
                </ul>

                <div class="flex items-center justify-between pt-1 border-t border-slate-100 font-body-sm text-body-sm text-on-surface-variant">
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">group</span> {{ $plan->subscribers_count }} {{ __('abòne') }}</span>
                    <span>#{{ $plan->sort_order }}</span>
                </div>
                <a href="{{ route('admin.plans.edit', $plan) }}" class="btn-secondary btn-sm">{{ __('Modifye') }}</a>
            </div>
        @endforeach
    </div>
</x-admin-layout>
