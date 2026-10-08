<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $canRemind = auth()->user()?->is_owner || \App\Support\Entitlements::for(auth()->user())->has('whatsapp_reminders');
        $isOpen = $cycle->status === 'open';
        $late = $cycle->isLate();
        $collected = (float) $contributions->sum('amount');
        $labels = ['cash' => __('Lajan kach'), 'moncash' => 'MonCash', 'natcash' => 'NatCash', 'bank' => __('Bank')];
        $icons = ['cash' => 'payments', 'moncash' => 'smartphone', 'natcash' => 'smartphone', 'bank' => 'account_balance'];
        $remind = function ($m) use ($tontine, $cycle) {
            $d = preg_replace('/\D/', '', (string) $m->phone);
            if (strlen($d) === 8) { $d = '509' . $d; }
            if ($d === '') { return null; }
            $msg = __('Bonjou :name, rapèl: kotizasyon :amount :cur pou :tontine (tou :n) dwe peye anvan :date.', [
                'name' => $m->name, 'amount' => number_format($tontine->amount_per_period, 0, '.', ','),
                'cur' => config('lajanansanm.currency'), 'tontine' => $tontine->name,
                'n' => $cycle->cycle_number, 'date' => $cycle->deadline->format('d/m/Y'),
            ]);
            return 'https://wa.me/' . $d . '?text=' . rawurlencode($msg);
        };
    @endphp

    <x-page-header :back="route('tontines.show', $tontine)" :title="__('Peman yo')" :subtitle="$tontine->name" />

    <div class="space-y-space-md">
        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert-err">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

        <div class="flex gap-2 overflow-x-auto pb-1 -mx-gutter px-gutter">
            @foreach ($cycles as $c)
                <a href="{{ route('tontines.contributions.index', [$tontine, 'cycle' => $c->id]) }}"
                   class="shrink-0 px-4 py-2 rounded-full font-label-md text-label-md {{ $c->id === $cycle->id ? 'bg-primary-container text-on-primary' : 'bg-surface-container-lowest shadow-sm text-on-surface-variant' }}">
                    {{ __('Tou') }} {{ $c->cycle_number }}
                </a>
            @endforeach
        </div>

        <div class="rounded-xl bg-gradient-to-br from-primary via-primary-container to-surface-tint text-on-primary p-space-lg shadow-xl space-y-space-md">
            <div class="flex items-center gap-3 bg-white/10 rounded-xl p-space-sm">
                <x-avatar :name="$cycle->beneficiary?->name" tone="bg-white text-primary-container" />
                <div class="min-w-0 flex-1">
                    <p class="font-body-sm text-body-sm text-primary-fixed">{{ __('Moun kap ranmase') }}</p>
                    <p class="font-title-md text-title-md truncate">{{ $cycle->beneficiary?->name ?? '—' }}</p>
                </div>
                <div class="text-right font-body-sm text-body-sm">
                    <p class="text-primary-fixed">{{ __('Dat limit') }}</p>
                    <p class="font-label-md text-label-md {{ $late ? 'text-secondary-container' : '' }}">{{ $cycle->deadline->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 tabular-nums">
                <div class="bg-white/10 rounded-xl p-space-sm"><p class="font-label-sm text-label-sm uppercase text-primary-fixed">{{ __('Lajan total') }}</p><p class="font-title-lg text-title-lg">{{ number_format($tontine->potAmount(), 0, '.', ',') }}</p></div>
                <div class="bg-white/10 rounded-xl p-space-sm"><p class="font-label-sm text-label-sm uppercase text-primary-fixed">{{ __('Ranmase') }}</p><p class="font-title-lg text-title-lg text-tertiary-fixed">{{ number_format($collected, 0, '.', ',') }}</p></div>
            </div>
            <div class="space-y-1">
                <div class="w-full h-3 bg-white/20 rounded-full overflow-hidden"><div class="h-3 rounded-full bg-emerald-400" style="width: {{ $progress['percent'] }}%"></div></div>
                <div class="flex justify-between font-body-sm text-body-sm"><span>{{ $progress['paid'] }} {{ __('sou') }} {{ $progress['total'] }} {{ __('peye') }}</span><span>{{ $progress['percent'] }}%</span></div>
            </div>
            @if ($isOpen)
                <a href="{{ route('tontines.payouts.index', $tontine) }}" class="btn bg-emerald-400 text-primary w-full">
                    <span class="material-symbols-outlined">payments</span> {{ __('Konfime vèsman bay') }} {{ \Illuminate\Support\Str::of($cycle->beneficiary?->name ?? '')->before(' ') }}
                </a>
            @else
                <p class="text-center font-body-sm text-body-sm text-primary-fixed">{{ $cycle->status === 'closed' ? __('Tou sa a fèmen.') : __('Tou sa a poko ouvè.') }}</p>
            @endif
        </div>

        <h2 class="font-title-lg text-title-lg pt-space-xs">{{ __('Estati manm yo pou tou sa a') }}</h2>

        <div class="space-y-space-sm">
            @foreach ($members as $member)
                @php $paid = $contributions->get($member->id); $wa = (! $paid && $isOpen && $canRemind) ? $remind($member) : null; @endphp
                <div class="card !p-space-sm" x-data="{ open: false }">
                    <div class="flex items-center gap-3">
                        <x-avatar :name="$member->name" :rank="$member->position" />
                        <div class="flex-1 min-w-0">
                            <p class="font-title-md text-title-md truncate">{{ $member->name }}
                                @if ($member->id === $cycle->beneficiary_member_id)<span class="badge-info ml-1">★</span>@endif
                            </p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant truncate">
                                @if ($paid) {{ $labels[$paid->payment_method] ?? $paid->payment_method }} · {{ $paid->paid_at->format('d/m/Y') }}@if ($paid->note) · {{ $paid->note }}@endif
                                @else {{ $member->phone ?: '—' }} @endif
                            </p>
                        </div>
                        @if ($paid)<span class="badge-paid">✓ {{ __('Peye') }}</span>
                        @elseif ($late)<span class="badge-late">{{ __('An reta') }}</span>
                        @else<span class="badge-wait">{{ __('Poko peye') }}</span>@endif
                    </div>

                    @if ($paid && $isOpen)
                        <form method="POST" action="{{ route('tontines.cycles.contributions.destroy', [$tontine, $cycle, $paid]) }}" class="text-right mt-1" onsubmit="return confirm('{{ __('Anile peman sa a?') }}')">
                            @csrf @method('DELETE')
                            <button class="font-label-md text-label-md text-secondary underline">{{ __('Anile') }}</button>
                        </form>
                    @elseif (! $paid && $isOpen)
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <button type="button" @click="open = !open" class="btn-primary btn-sm"><span class="material-symbols-outlined text-[18px]">add_card</span> {{ __('Anrejistre peman') }}</button>
                            @if ($wa)
                                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-secondary btn-sm {{ $late ? '!bg-secondary !text-on-secondary !border-0' : '' }}"><span class="material-symbols-outlined text-[18px]">notifications_active</span> {{ __('Voye rapèl') }}</a>
                            @elseif (! $paid && $isOpen && ! $canRemind)
                                <a href="{{ route('plans') }}" class="btn-secondary btn-sm opacity-70"><span class="material-symbols-outlined text-[18px]">lock</span> {{ __('Rapèl (Premium)') }}</a>
                            @endif
                        </div>

                        <form x-show="open" x-cloak x-transition method="POST" action="{{ route('tontines.cycles.contributions.store', [$tontine, $cycle]) }}" class="mt-space-sm space-y-space-md" x-data="{ m: 'cash' }">
                            @csrf
                            <input type="hidden" name="member_id" value="{{ $member->id }}">
                            <div class="flex justify-between items-center bg-primary-fixed/60 rounded-xl p-space-sm tabular-nums">
                                <span class="font-label-md text-label-md">{{ __('Total pou anrejistre') }}</span>
                                <span class="font-title-lg text-title-lg text-primary-container">{{ number_format($tontine->amount_per_period, 0, '.', ',') }} {{ $cur }}</span>
                            </div>
                            <div>
                                <span class="label">{{ __('Metòd peman') }}</span>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($methods as $mm)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="payment_method" value="{{ $mm }}" class="peer sr-only" x-model="m" required>
                                            <span class="flex items-center gap-2 p-space-sm rounded-xl border-[1.5px] border-slate-200 bg-white font-label-md text-label-md peer-checked:border-primary-container peer-checked:bg-primary-fixed/60 transition">
                                                <span class="material-symbols-outlined text-primary-container">{{ $icons[$mm] }}</span>{{ $labels[$mm] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="label">{{ __('Dat peman an') }}</label>
                                <input type="date" name="paid_at" class="input" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div>
                                <label class="label">{{ __('Nòt (opsyonèl)') }}</label>
                                <input name="note" class="input" maxlength="500" placeholder="MC-89230193">
                            </div>
                            <button class="btn-primary w-full"><span class="material-symbols-outlined">receipt_long</span> {{ __('Konfime peman') }} {{ number_format($tontine->amount_per_period, 0, '.', ',') }} {{ $cur }}</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
