<x-admin-layout active="requests" :title="__('Demann peman')">
    @php
        $cur = config('lajanansanm.currency');
        $periodLabel = ['monthly' => __('Chak mwa'), 'quarterly' => __('3 mwa'), 'yearly' => __('Chak ane')];
        $tabs = ['pending' => __('Ap tann'), 'approved' => __('Konfime'), 'rejected' => __('Rejte')];
        $methodLabel = \App\Models\SubscriptionRequest::METHODS;
    @endphp

    <div class="mb-space-lg">
        <h1 class="font-headline-lg text-headline-lg">{{ __('Demann peman ak validasyon') }}</h1>
        <p class="text-on-surface-variant">{{ __('Verifye peman MonCash, NatCash oswa bank yo anvan ou aktive yon abònman.') }}</p>
    </div>

    @if ($errors->has('request'))<div class="alert-err mb-space-md">{{ $errors->first('request') }}</div>@endif

    <div class="flex gap-2 mb-space-md font-label-md text-label-md">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.requests', ['status' => $key]) }}" class="px-4 py-2 rounded-full {{ $status === $key ? 'bg-primary-container text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }}">
                {{ $label }} <span class="ml-1 opacity-80">({{ $counts[$key] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    <div class="space-y-space-md">
        @forelse ($requests as $r)
            @php $waiting = $r->created_at->diffInHours(now()); @endphp
            <div class="card !p-space-lg space-y-space-md" x-data="{ open: null }" data-request="{{ $r->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-title-lg text-title-lg">{{ $r->user->name }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $r->user->email }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-headline-md text-headline-md tabular-nums text-primary-container">{{ number_format($r->amount, 0, '.', ',') }} {{ $cur }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $r->plan->name }} · {{ $periodLabel[$r->billing_period] }}</p>
                    </div>
                </div>

                <dl class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 font-body-sm text-body-sm">
                    <div><dt class="text-on-surface-variant">{{ __('Metòd') }}</dt><dd class="font-label-lg text-label-lg">{{ $methodLabel[$r->payment_method] ?? $r->payment_method }}</dd></div>
                    <div><dt class="text-on-surface-variant">{{ __('Telefòn moun ki peye a') }}</dt><dd class="font-label-lg text-label-lg tabular-nums">{{ $r->payer_phone }}</dd></div>
                    <div><dt class="text-on-surface-variant">{{ __('Kòd tranzaksyon') }}</dt><dd class="font-label-lg text-label-lg font-mono">{{ $r->transaction_ref }}</dd></div>
                    <div><dt class="text-on-surface-variant">{{ __('Referans nòt') }}</dt><dd class="font-label-lg text-label-lg font-mono">{{ $r->reference_code }}</dd></div>
                </dl>
                @if ($r->note)<p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Nòt') }} : {{ $r->note }}</p>@endif

                @if ($r->status === 'pending')
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="{{ $waiting >= 24 ? 'badge-late' : 'badge-wait' }}">{{ __('Ap tann depi') }} {{ $r->created_at->locale(app()->getLocale())->diffForHumans(['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</span>
                        <div class="flex gap-2">
                            <button type="button" @click="open = open === 'approve' ? null : 'approve'" class="btn-success btn-sm"><span class="material-symbols-outlined text-[18px]">check</span> {{ __('Konfime ak aktive') }}</button>
                            <button type="button" @click="open = open === 'reject' ? null : 'reject'" class="btn-secondary btn-sm !text-secondary"><span class="material-symbols-outlined text-[18px]">close</span> {{ __('Rejte') }}</button>
                        </div>
                    </div>

                    <form x-show="open === 'approve'" x-cloak method="POST" action="{{ route('admin.requests.approve', $r) }}" class="rounded-xl bg-emerald-50 p-space-md grid sm:grid-cols-2 gap-space-sm">
                        @csrf
                        <div>
                            <label class="label">{{ __('Nouvo dat ekspirasyon (opsyonèl)') }}</label>
                            <input type="date" name="ends_at" class="input" min="{{ now()->addDay()->format('Y-m-d') }}">
                            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ __('Vid = nòmal selon peryòd la.') }}</p>
                        </div>
                        <div><label class="label">{{ __('Nòt entèn (opsyonèl)') }}</label><input name="note" class="input" maxlength="300"></div>
                        <button class="btn-success sm:col-span-2" onclick="return confirm('{{ __('Konfime peman an epi aktive abònman an?') }}')">{{ __('Konfime epi aktive') }}</button>
                    </form>

                    <form x-show="open === 'reject'" x-cloak method="POST" action="{{ route('admin.requests.reject', $r) }}" class="rounded-xl bg-error-container/40 p-space-md space-y-space-sm">
                        @csrf
                        <div><label class="label">{{ __('Rezon (kliyan an ap wè l)') }}</label><input name="note" class="input" maxlength="300" required></div>
                        <button class="btn-danger">{{ __('Rejte demann lan') }}</button>
                    </form>
                @else
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ $r->status === 'approved' ? __('Konfime') : __('Rejte') }} {{ __('pa') }} {{ $r->reviewer?->name ?? '—' }} · {{ $r->reviewed_at?->format('d/m/Y H:i') }}
                        @if ($r->review_note) · {{ $r->review_note }} @endif
                    </p>
                @endif
            </div>
        @empty
            <x-empty-state icon="payments" :title="__('Pa gen demann isit la')" :text="__('Demann abònman yo ap parèt isit la.')" />
        @endforelse
    </div>
    <div class="mt-space-md">{{ $requests->links() }}</div>
</x-admin-layout>
