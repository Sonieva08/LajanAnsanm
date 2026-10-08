<x-error-layout code="404" icon="explore" :title="__('Paj sa a pa egziste')" :message="__('Nou pa ka jwenn paj w ap chèche a. Li posib adrès la chanje oswa li pa disponib ankò.')">
    <a href="{{ url('/dashboard') }}" class="btn-primary flex-1">{{ __('Retounen sou Akèy') }} <span class="material-symbols-outlined">arrow_forward</span></a>
    <a href="{{ url('/tontines') }}" class="btn-secondary flex-1 !bg-surface-container-low"><span class="material-symbols-outlined">account_balance_wallet</span> {{ __('Gade Tontin Mwen Yo') }}</a>
</x-error-layout>
