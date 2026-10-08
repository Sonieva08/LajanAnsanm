<x-error-layout :code="isset($exception) ? $exception->getStatusCode() : 400" icon="report" :title="__('Demann nan pa bon')" :message="__('Nou pa t kapab trete demann sa a. Retounen epi eseye ankò.')">
    <a href="{{ url('/dashboard') }}" class="btn-primary flex-1"><span class="material-symbols-outlined">home</span> {{ __('Retounen sou Akèy') }}</a>
    <button type="button" onclick="history.back()" class="btn-secondary flex-1 !bg-surface-container-low"><span class="material-symbols-outlined">undo</span> {{ __('Retounen') }}</button>
</x-error-layout>
