<x-error-layout code="403" icon="lock" :title="__('Ou pa gen aksè isit la')" :message="__('Ou dwe konekte kòm responsab tontin sa a pou w ka wè pati sa a.')">
    <a href="{{ url('/dashboard') }}" class="btn-primary flex-1"><span class="material-symbols-outlined">home</span> {{ __('Retounen sou Akèy') }}</a>
    <button type="button" onclick="history.back()" class="btn-secondary flex-1 !bg-surface-container-low"><span class="material-symbols-outlined">undo</span> {{ __('Retounen') }}</button>
</x-error-layout>
