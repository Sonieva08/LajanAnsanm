@php
    $message = ($message ?? null) ?: __('LajanAnsanm ap amelyore. Done ou yo an sekirite. Nou tounen talè.');
    $endsAt = $endsAt ?? null;
    $waDigits = preg_replace('/\D/', '', (string) config('lajanansanm.support_whatsapp'));
@endphp
<x-public-shell :title="__('Nou ap fè antretyen')" :refresh="60" :status="__('Antretyen an kou')">
    <div class="w-full bg-surface-container-lowest rounded-xl shadow-sm p-space-md flex flex-col items-center text-center">
        <div class="relative w-44 h-40 flex items-center justify-center my-space-xs" aria-hidden="true">
            <div class="absolute w-32 h-32 rounded-full bg-surface-container-high blur-xl opacity-70"></div>
            <svg class="w-full h-full relative z-10" fill="none" viewBox="0 0 180 160" xmlns="http://www.w3.org/2000/svg">
                <circle cx="28" cy="38" fill="#7f91f1" opacity="0.45" r="3.5"/>
                <circle cx="152" cy="42" fill="#00ac77" opacity="0.6" r="2.5"/>
                <circle cx="145" cy="115" fill="#bac3ff" opacity="0.5" r="4"/>
                <circle cx="34" cy="120" fill="#0c2382" opacity="0.3" r="2"/>
                <g style="transform-origin: 75px 70px; animation: lajan-spin 18s linear infinite;">
                    <circle cx="75" cy="70" fill="#f2f3ff" r="36"/>
                    <path d="M75 22V30M75 110V118M27 70H35M115 70H123M41 36L47 42M103 98L109 104M41 104L47 98M103 42L109 36" stroke="#0c2382" stroke-linecap="round" stroke-width="6.5"/>
                    <circle cx="75" cy="70" fill="#eaedff" r="28"/>
                    <circle cx="75" cy="70" fill="#0c2382" r="18"/>
                    <circle cx="75" cy="70" fill="#ffffff" r="8"/>
                </g>
                <g style="transform-origin: 118px 98px; animation: lajan-spin-reverse 12s linear infinite;">
                    <circle cx="118" cy="98" fill="#faf8ff" r="24"/>
                    <path d="M118 66V72M118 124V130M86 98H92M144 98H150M95 75L100 80M136 121L141 126M95 121L100 116M136 75L141 80" stroke="#7f91f1" stroke-linecap="round" stroke-width="4.5"/>
                    <circle cx="118" cy="98" fill="#bac3ff" r="17"/>
                    <circle cx="118" cy="98" fill="#001058" r="7"/>
                </g>
                <path d="M42 46L44.2 51.8L50 54L44.2 56.2L42 62L39.8 56.2L34 54L39.8 51.8L42 46Z" fill="#00ac77"/>
            </svg>
        </div>

        <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-primary font-extrabold tracking-tight">{{ __('Nou ap fè antretyen') }}</h1>
        <p class="font-title-md text-title-md text-on-surface mt-1 max-w-sm">{{ $message }}</p>

        @if ($endsAt)
            <div class="mt-space-md inline-flex items-center gap-2 bg-surface-container-high px-space-md py-2 rounded-full">
                <span class="material-symbols-outlined text-primary text-[19px]" style="font-variation-settings: 'FILL' 1;">schedule</span>
                <span class="font-label-md text-label-md text-primary font-bold">{{ __('Nou espere tounen') }} : {{ $endsAt->format('d/m/Y H:i') }}</span>
            </div>
        @endif

        <div class="flex items-center justify-center gap-2 mt-space-md">
            <span class="font-label-md text-label-md text-on-surface-variant">{{ __('Sistèm ap optimize') }}</span>
            <div class="flex items-center gap-1.5 pt-0.5">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-bounce" style="animation-delay: 0s"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-bounce" style="animation-delay: .2s"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-bounce" style="animation-delay: .4s"></span>
            </div>
        </div>

        <button type="button" onclick="location.assign(location.href)" class="btn-primary w-full mt-space-md">
            <span class="material-symbols-outlined">autorenew</span> {{ __('Rechaje paj la') }}
        </button>

        <div class="w-full mt-space-sm flex flex-col items-center">
            <div class="w-full bg-surface-container h-1.5 rounded-full overflow-hidden">
                <div id="mt-bar" class="bg-on-tertiary-container h-full transition-all duration-1000 ease-linear rounded-full" style="width: 100%"></div>
            </div>
            <div class="flex items-center justify-between w-full mt-2 text-on-surface-variant">
                <span class="flex items-center gap-1.5 font-body-sm text-body-sm"><span class="material-symbols-outlined text-[15px] text-outline">sync</span> {{ __('Paj la rechaje otomatikman') }}</span>
                <span class="font-label-sm text-label-sm text-primary" id="mt-label">{{ __('nan') }} 60s</span>
            </div>
        </div>
    </div>

    @if ($waDigits !== '')
        <div class="w-full flex flex-col items-center text-center px-space-sm">
            <p class="font-body-sm text-body-sm text-on-surface-variant mb-1.5">{{ __('Ou bezwen asistans ijan oswa ou gen yon kesyon sou sòl ou?') }}</p>
            <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 bg-surface-container-highest px-space-md py-2 rounded-full">
                <span class="material-symbols-outlined text-on-tertiary-container text-[18px]" style="font-variation-settings: 'FILL' 1;">chat</span>
                <span class="font-label-md text-label-md text-primary">{{ __('Kontakte sipò sou WhatsApp') }}</span>
            </a>
        </div>
    @endif

    <script>
        (function () {
            var total = 60, left = total;
            var label = document.getElementById('mt-label'), bar = document.getElementById('mt-bar');
            var word = @js(__('nan'));
            setInterval(function () {
                left--;
                if (left <= 0) { location.assign(location.href); return; }
                label.textContent = word + ' ' + left + 's';
                bar.style.width = (left / total * 100) + '%';
            }, 1000);
        })();
    </script>
</x-public-shell>
