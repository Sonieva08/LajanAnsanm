@php
    $seconds = (isset($exception) && method_exists($exception, 'getHeaders')) ? max(1, (int) ($exception->getHeaders()['Retry-After'] ?? 60)) : 60;
@endphp
<x-error-layout code="429" icon="speed" :title="__('Twòp demann')" :message="__('Sistèm lan ap pran yon ti souf pou pwoteje tranzaksyon yo. Tanpri tann kèk segond.')">
    <x-slot:extra>
        <div class="bg-surface-container-low rounded-xl p-space-sm flex flex-col gap-1.5">
            <div class="flex items-center justify-between font-label-md text-label-md text-primary">
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-title-md">sync</span> {{ __('Re-eseye otomatikman') }}</span>
                <span class="font-title-md text-title-md" id="cd-label">{{ $seconds }}s</span>
            </div>
            <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                <div class="bg-primary-container h-full rounded-full transition-all duration-1000 ease-linear" id="cd-bar" style="width: 100%"></div>
            </div>
        </div>
        <script>
            (function () {
                var total = {{ $seconds }}, left = total;
                var label = document.getElementById('cd-label'), bar = document.getElementById('cd-bar');
                var t = setInterval(function () {
                    left--;
                    if (left <= 0) { clearInterval(t); location.assign(location.href); return; }
                    label.textContent = left + 's';
                    bar.style.width = (left / total * 100) + '%';
                }, 1000);
            })();
        </script>
    </x-slot:extra>
    <button type="button" onclick="location.assign(location.href)" class="btn-primary flex-1"><span class="material-symbols-outlined">replay</span> {{ __('Re-eseye kounye a') }}</button>
    <a href="{{ url('/dashboard') }}" class="btn-secondary flex-1 !bg-surface-container-low"><span class="material-symbols-outlined">home</span> {{ __('Retounen sou Akèy') }}</a>
</x-error-layout>
