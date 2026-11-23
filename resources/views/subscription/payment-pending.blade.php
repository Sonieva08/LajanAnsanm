<x-app-layout>
    @php
        $periodLabels = [
            'monthly' => __('Chak mwa'),
            'quarterly' => __('3 mwa'),
            'yearly' => __('Chak ane'),
        ];
    @endphp

    <x-page-header :back="route('subscription')" :title="__('Peman an ap verifye')" />

    <div class="mx-auto max-w-2xl space-y-space-md" data-payment-pending>
        <section class="card !p-space-lg space-y-space-md text-center" aria-live="polite" aria-atomic="true">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary-fixed text-primary-container">
                <span class="material-symbols-outlined text-[34px] animate-spin" id="pending-icon" aria-hidden="true">progress_activity</span>
            </div>

            <div class="space-y-1">
                <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-3 py-1 font-label-sm text-label-sm text-primary-container">
                    <span class="material-symbols-outlined text-[15px]" aria-hidden="true">sync</span>
                    {{ __('Tranzaksyon an ap verifye') }}
                </span>
                <h2 class="font-headline-lg-mobile text-headline-lg-mobile">{{ __('Nou ap tann konfimasyon MonCash') }}</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Pa fèmen paj la. Plan ou aktive otomatikman le peman an konfime.') }}</p>
            </div>

            <div class="rounded-xl bg-surface-container-low p-space-md text-left">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Plan') }}</dt>
                        <dd class="text-right font-label-md text-label-md font-semibold">{{ $payment->plan->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Peryòd') }}</dt>
                        <dd class="text-right font-label-md text-label-md">{{ $periodLabels[$payment->billing_period] ?? $payment->billing_period }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-surface-container-highest pt-3">
                        <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Montan') }}</dt>
                        <dd class="text-right font-title-lg text-title-lg tabular-nums text-primary-container">
                            {{ number_format((float) $payment->amount, 0, '.', ',') }} {{ config('lajanansanm.currency') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Referans peman') }}</dt>
                        <dd class="break-all text-right font-label-sm text-label-sm font-mono">{{ $payment->reference_id }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-primary-container/15 bg-primary-fixed/40 p-space-md text-left">
                <div class="flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5 text-primary-container" aria-hidden="true">info</span>
                    <div>
                        <p class="font-label-md text-label-md">{{ __('Sa pou w fè kounye a') }}</p>
                        <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ __('Swiv enstriksyon MonCash sou telefòn ou pou otorize tranzaksyon an. Pa pataje PIN ou ak pèsonn; LajanAnsanm pa mande ni estoke PIN MonCash ou.') }}</p>
                    </div>
                </div>
            </div>

            <p class="hidden rounded-xl bg-surface-container-low p-space-md text-left font-body-sm text-body-sm text-on-surface-variant" id="pending-timeout" role="status">
                {{ __('Konfimasyon an pran plis tan pase nòmal. Si kont ou debite, pa peye ankò: nou kontinye verifye tranzaksyon an, epi plan an aktive otomatikman lè MonCash konfime peman an.') }}
            </p>
            <p class="hidden text-left font-body-sm text-body-sm text-secondary" id="pending-connection-error" role="status">
                {{ __('Nou pa rive rafrechi estati peman an kounye a. N ap eseye ankò otomatikman.') }}
            </p>

            <a href="{{ route('subscription') }}" class="btn-secondary w-full">{{ __('Retounen nan abònman an') }}</a>
        </section>
    </div>

    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (function () {
            const statusUrl = @js(route('subscription.online.status', $payment->reference_id));
            const doneUrl = @js(route('subscription.online.return', $payment->reference_id));
            const delayedNotice = document.getElementById('pending-timeout');
            const connectionNotice = document.getElementById('pending-connection-error');
            let attempts = 0;
            let timer;

            async function refreshPaymentStatus() {
                attempts++;

                try {
                    const response = await fetch(statusUrl, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Payment status request failed.');
                    }

                    const data = await response.json();
                    connectionNotice.classList.add('hidden');

                    if (data.status && data.status !== 'pending') {
                        window.location.href = doneUrl;
                        return;
                    }
                } catch (error) {
                    connectionNotice.classList.remove('hidden');
                }

                if (attempts >= 30) {
                    delayedNotice.classList.remove('hidden');
                    document.getElementById('pending-icon').classList.remove('animate-spin');
                }

                timer = window.setTimeout(refreshPaymentStatus, attempts < 30 ? 3000 : 15000);
            }

            timer = window.setTimeout(refreshPaymentStatus, 3000);
            window.addEventListener('pagehide', function () {
                window.clearTimeout(timer);
            }, { once: true });
        })();
    </script>
</x-app-layout>
