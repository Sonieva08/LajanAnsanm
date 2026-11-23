<x-admin-layout active="subscriptions" :title="__('Lis abònman yo')">
    @php
        $currency = config('lajanansanm.currency');
        $periodLabels = ['monthly' => __('Chak mwa'), 'quarterly' => __('3 mwa'), 'yearly' => __('Chak ane')];
        $sourceLabels = ['online' => 'MonCashConnect', 'request' => __('Demann manyèl'), 'owner_grant' => __('Bay pa pwopriyetè')];
        $statusLabels = [
            'active' => __('Aktif'),
            'expired' => __('Ekspire'),
            'replaced' => __('Ranplase'),
            'cancelled' => __('Anile'),
            'pending' => __('Ap tann'),
            'completed' => __('Konfime'),
            'failed' => __('Echwe'),
            'review' => __('Pou verifye'),
        ];
    @endphp

    <div class="mb-space-lg">
        <h1 class="font-headline-lg text-headline-lg">{{ __('Lis abònman yo') }}</h1>
        <p class="text-on-surface-variant max-w-3xl">{{ __('Gade abònman aktif ak ansyen yo, ansanm ak tantativ peman MonCashConnect yo. Yon peman parèt kòm abònman sèlman apre konfimasyon li.') }}</p>
    </div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-5 gap-space-sm mb-space-lg">
        @foreach (['all' => __('Tout'), 'active' => __('Aktif'), 'expired' => __('Ekspire'), 'replaced' => __('Ranplase'), 'cancelled' => __('Anile')] as $key => $label)
            <a href="{{ route('admin.subscriptions', array_filter(['status' => $key === 'all' ? null : $key, 'source' => $source !== 'all' ? $source : null, 'q' => $search ?: null])) }}" class="card !p-space-sm {{ $status === $key ? 'ring-2 ring-primary-container' : '' }}">
                <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $label }}</span>
                <span class="block font-headline-md text-headline-md tabular-nums">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <section class="card !p-space-md mb-space-lg">
        <form method="GET" action="{{ route('admin.subscriptions') }}" class="grid sm:grid-cols-[1fr_auto_auto] gap-space-sm items-end">
            <div>
                <label for="subscription-search" class="label">{{ __('Rechèch') }}</label>
                <input id="subscription-search" class="input" name="q" value="{{ $search }}" placeholder="{{ __('Non, imèl, plan oswa referans peman') }}">
            </div>
            <div>
                <label for="subscription-source" class="label">{{ __('Sous') }}</label>
                <select id="subscription-source" class="input" name="source">
                    <option value="all" @selected($source === 'all')>{{ __('Tout sous yo') }}</option>
                    <option value="online" @selected($source === 'online')>MonCashConnect</option>
                    <option value="request" @selected($source === 'request')>{{ __('Demann manyèl') }}</option>
                    <option value="owner_grant" @selected($source === 'owner_grant')>{{ __('Bay pa pwopriyetè') }}</option>
                </select>
            </div>
            <input type="hidden" name="status" value="{{ $status }}">
            <button class="btn-primary">{{ __('Chèche') }}</button>
        </form>
    </section>

    <section class="card !p-0 overflow-hidden mb-space-lg">
        <div class="px-space-md py-space-sm border-b border-slate-100">
            <h2 class="font-title-lg text-title-lg">{{ __('Abònman') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-sm text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant">
                    <tr>
                        <th class="px-space-md py-3 font-label-md">{{ __('Itilizatè') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Plan') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Sous / peman') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Montan') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Estati') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Dat yo') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($subscriptions as $subscription)
                        @php
                            $expired = $subscription->status === 'active' && $subscription->ends_at->isPast();
                            $displayStatus = $subscription->status === 'active' && $expired ? 'expired' : $subscription->status;
                        @endphp
                        <tr>
                            <td class="px-space-md py-3">
                                <p class="font-label-lg text-label-lg">{{ $subscription->user->name }}</p>
                                <p class="text-on-surface-variant">{{ $subscription->user->email }}</p>
                            </td>
                            <td class="px-space-md py-3">
                                <p class="font-label-lg text-label-lg">{{ $subscription->plan->name }}</p>
                                <p class="text-on-surface-variant">{{ $periodLabels[$subscription->billing_period] ?? '—' }}</p>
                            </td>
                            <td class="px-space-md py-3">
                                <p class="font-label-lg text-label-lg">{{ $sourceLabels[$subscription->source] ?? $subscription->source }}</p>
                                @if ($subscription->payment)
                                    <p class="font-mono text-xs text-on-surface-variant">{{ $subscription->payment->reference_id }}</p>
                                    <p class="text-on-surface-variant">{{ $subscription->payment->provider }} · {{ $statusLabels[$subscription->payment->status] ?? $subscription->payment->status }}</p>
                                @elseif ($subscription->note)
                                    <p class="text-on-surface-variant">{{ $subscription->note }}</p>
                                @endif
                            </td>
                            <td class="px-space-md py-3 tabular-nums">{{ number_format((float) $subscription->amount, 0, '.', ',') }} {{ $currency }}</td>
                            <td class="px-space-md py-3"><span class="{{ $displayStatus === 'active' ? 'badge-paid' : 'badge-muted' }}">{{ $statusLabels[$displayStatus] ?? $displayStatus }}</span></td>
                            <td class="px-space-md py-3 whitespace-nowrap">
                                <p>{{ $subscription->starts_at->format('d/m/Y') }} – {{ $subscription->ends_at->format('d/m/Y') }}</p>
                                <p class="text-on-surface-variant">{{ $subscription->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-space-md py-space-lg text-center text-on-surface-variant">{{ __('Pa gen abònman ki koresponn ak rechèch sa a.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-space-md py-space-sm">{{ $subscriptions->links() }}</div>
    </section>

    <section class="card !p-0 overflow-hidden">
        <div class="px-space-md py-space-sm border-b border-slate-100">
            <h2 class="font-title-lg text-title-lg">{{ __('Dènye tantativ peman MonCashConnect') }}</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Peman ki poko konfime yo parèt la, men yo pa konte kòm abònman.') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-sm text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant">
                    <tr>
                        <th class="px-space-md py-3 font-label-md">{{ __('Itilizatè') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Plan / montan') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Referans') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Estati') }}</th>
                        <th class="px-space-md py-3 font-label-md">{{ __('Dat') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-space-md py-3">
                                <p class="font-label-lg text-label-lg">{{ $payment->user->name }}</p>
                                <p class="text-on-surface-variant">{{ $payment->user->email }}</p>
                            </td>
                            <td class="px-space-md py-3">
                                <p class="font-label-lg text-label-lg">{{ $payment->plan->name }}</p>
                                <p class="tabular-nums text-on-surface-variant">{{ number_format((float) $payment->amount, 0, '.', ',') }} {{ $currency }}</p>
                            </td>
                            <td class="px-space-md py-3 font-mono text-xs">{{ $payment->reference_id }}</td>
                            <td class="px-space-md py-3">
                                <span class="{{ $payment->status === 'completed' ? 'badge-paid' : 'badge-muted' }}">{{ $statusLabels[$payment->status] ?? $payment->status }}</span>
                                @if ($payment->failure_reason)<p class="mt-1 text-secondary">{{ $payment->failure_reason }}</p>@endif
                            </td>
                            <td class="px-space-md py-3 whitespace-nowrap">
                                <p>{{ $payment->completed_at?->format('d/m/Y H:i') ?? '—' }}</p>
                                <p class="text-on-surface-variant">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-space-md py-space-lg text-center text-on-surface-variant">{{ __('Pa gen tantativ peman MonCashConnect toujou.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-space-md py-space-sm">{{ $payments->links() }}</div>
    </section>
</x-admin-layout>
