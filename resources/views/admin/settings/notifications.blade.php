<x-admin-layout active="settings" :title="__('Notifikasyon')">
    <div class="mb-space-lg">
        <h1 class="font-headline-lg text-headline-lg text-primary">{{ __('Notifikasyon') }}</h1>
        <p class="text-on-surface-variant">{{ __('Mesaj rapèl yo voye pa WhatsApp bay manm ki an reta.') }}</p>
    </div>
    @include('admin.settings._tabs', ['active' => 'notifications'])

    @if (session('status'))<div class="alert-ok mb-space-md">{{ session('status') }}</div>@endif

    <div class="grid lg:grid-cols-2 gap-space-md items-start">
        <form method="POST" action="{{ route('admin.settings.notifications.update') }}" class="card space-y-space-md" x-data="{ t: @js($template) }">
            @csrf
            <h2 class="font-title-lg text-title-lg">{{ __('Modèl mesaj la') }}</h2>
            <textarea name="template" rows="4" maxlength="300" x-model="t" required class="input !min-h-0">{{ old('template', $template) }}</textarea>
            <x-input-error :messages="$errors->get('template')" />
            <div class="flex flex-wrap gap-2">
                @foreach (['{nom}', '{montan}', '{cur}', '{tontin}', '{tou}', '{dat}'] as $ph)
                    <button type="button" class="badge-info" @click="t += '{{ $ph }}'">{{ $ph }}</button>
                @endforeach
            </div>
            <button class="btn-primary">{{ __('Sove modèl la') }}</button>
        </form>

        <div class="card space-y-space-sm">
            <h2 class="font-title-lg text-title-lg">{{ __('Aperçu') }}</h2>
            <p class="bg-surface-container-low rounded-xl p-space-md font-body-md text-body-md">{{ $sample }}</p>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('SMS poko konekte. Rapèl yo voye pa WhatsApp, lè òganizatè a peze bouton an.') }}</p>
        </div>
    </div>
</x-admin-layout>
