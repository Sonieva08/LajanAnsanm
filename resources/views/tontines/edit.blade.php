<x-app-layout>
    <x-page-header :back="route('tontines.show', $tontine)" :title="__('Modifye tontine')" :subtitle="$tontine->name" />

    <form method="POST" action="{{ route('tontines.update', $tontine) }}" class="card !p-space-lg space-y-space-lg">
        @method('PUT')
        @include('tontines._form')
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('tontines.show', $tontine) }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
