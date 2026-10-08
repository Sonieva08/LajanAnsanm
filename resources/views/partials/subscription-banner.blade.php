@auth
    @unless (auth()->user()->is_owner)
        @php
            $__ent = \App\Support\Entitlements::for(auth()->user());
            $__remind = \App\Models\Setting::subscriptionRules()['remind_days'];
            $__ro = $__ent->readOnlyCount();
        @endphp

        @if (session('subscription_notice'))
            <div class="mb-space-md flex items-center gap-2 bg-error-container/70 text-on-error-container rounded-xl px-4 py-3 font-label-md text-label-md" data-subscription-notice>
                <span class="material-symbols-outlined text-[20px]">lock</span><span class="flex-1">{{ session('subscription_notice') }}</span>
                <a href="{{ route('plans') }}" class="underline whitespace-nowrap">{{ __('Wè plan yo') }}</a>
            </div>
        @endif

        @if ($__ent->state === 'grace')
            <div class="mb-space-md flex items-center gap-2 bg-error-container/70 text-on-error-container rounded-xl px-4 py-3" data-grace-banner>
                <span class="material-symbols-outlined text-[20px] shrink-0">hourglass_bottom</span>
                <p class="flex-1 font-label-md text-label-md">{{ __('Abònman ou fini. Ou gen') }} {{ $__ent->graceDaysLeft() }} {{ __('jou pou renouvle.') }}</p>
                <a href="{{ route('plans') }}" class="btn-danger btn-sm">{{ __('Renouvle') }}</a>
            </div>
        @elseif ($__ent->state === 'active' && $__ent->daysLeft() <= $__remind)
            <div class="mb-space-md flex items-center gap-2 bg-amber-50 text-amber-900 rounded-xl px-4 py-3" data-expiring-banner>
                <span class="material-symbols-outlined text-[20px] shrink-0">schedule</span>
                <p class="flex-1 font-label-md text-label-md">{{ __('Abònman ou ap fini nan') }} {{ max(0, $__ent->daysLeft()) }} {{ __('jou.') }}</p>
                <a href="{{ route('plans') }}" class="btn-primary btn-sm">{{ __('Renouvle') }}</a>
            </div>
        @elseif ($__ent->state === 'expired' && $__ro > 0)
            <div class="mb-space-md flex items-center gap-2 bg-error-container/70 text-on-error-container rounded-xl px-4 py-3" data-expired-banner>
                <span class="material-symbols-outlined text-[20px] shrink-0">lock</span>
                <p class="flex-1 font-label-md text-label-md">{{ __('Abònman ou fini. :n tontin an lekti sèlman.', ['n' => $__ro]) }}</p>
                <a href="{{ route('plans') }}" class="btn-danger btn-sm">{{ __('Renouvle') }}</a>
            </div>
        @endif
    @endunless
@endauth
