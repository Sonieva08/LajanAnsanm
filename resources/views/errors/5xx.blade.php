<x-error-layout :code="isset($exception) ? $exception->getStatusCode() : 500" icon="build" tone="red" :title="__('Gen yon pwoblèm sou sèvè a')" :message="__('Se pa fòt ou. Done ou yo an sekirite.') . ' ' . __('Eseye ankò nan kèk minit.')">
    <button type="button" onclick="location.assign(location.href)" class="btn-primary flex-1"><span class="material-symbols-outlined">refresh</span> {{ __('Eseye ankò') }}</button>
    <a href="{{ url('/') }}" class="btn-secondary flex-1 !bg-surface-container-low"><span class="material-symbols-outlined">home</span> {{ __('Paj dakèy') }}</a>
</x-error-layout>
