<x-admin-layout active="announcements" :title="__('Anons')">
    @php $aud = ['all' => __('Tout moun'), 'free' => __('Plan gratis sèlman'), 'paid' => __('Abonè sèlman')]; $kind = ['info' => __('Enfòmasyon'), 'warning' => __('Avètisman'), 'danger' => __('Ijan')]; @endphp

    <div class="mb-space-lg">
        <h1 class="font-headline-lg text-headline-lg text-primary">{{ __('Anons pou itilizatè yo') }}</h1>
        <p class="text-on-surface-variant">{{ __('Yon bandwòl ki parèt an wo paj aplikasyon an. Ou chwazi ki moun ki wè li.') }}</p>
    </div>

    @if (session('status'))<div class="alert-ok mb-space-md">{{ session('status') }}</div>@endif

    <div class="grid lg:grid-cols-12 gap-space-md items-start">
        <form method="POST" action="{{ route('admin.announcements.store') }}" class="lg:col-span-5 card space-y-space-md">
            @csrf
            <div>
                <label class="label" for="title">{{ __('Tit') }}</label>
                <input id="title" name="title" class="input" maxlength="120" required value="{{ old('title') }}">
                <x-input-error :messages="$errors->get('title')" />
            </div>
            <div>
                <label class="label" for="message">{{ __('Mesaj (kout)') }}</label>
                <textarea id="message" name="message" rows="3" maxlength="300" required class="input !min-h-0">{{ old('message') }}</textarea>
                <x-input-error :messages="$errors->get('message')" />
            </div>
            <div class="grid grid-cols-2 gap-space-sm">
                <div>
                    <label class="label" for="type">{{ __('Kalite') }}</label>
                    <select id="type" name="type" class="input">
                        @foreach ($kind as $k => $l)<option value="{{ $k }}" @selected(old('type', 'info') === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="audience">{{ __('Ki moun ki wè') }}</label>
                    <select id="audience" name="audience" class="input">
                        @foreach ($aud as $k => $l)<option value="{{ $k }}" @selected(old('audience', 'all') === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-space-sm">
                <div>
                    <label class="label" for="starts_at">{{ __('Soti (opsyonèl)') }}</label>
                    <input id="starts_at" type="date" name="starts_at" class="input" value="{{ old('starts_at') }}">
                </div>
                <div>
                    <label class="label" for="ends_at">{{ __('Jiska (opsyonèl)') }}</label>
                    <input id="ends_at" type="date" name="ends_at" class="input" value="{{ old('ends_at') }}">
                    <x-input-error :messages="$errors->get('ends_at')" />
                </div>
            </div>
            <button class="btn-primary w-full">{{ __('Pibliye anons lan') }}</button>
        </form>

        <div class="lg:col-span-7 card !p-0 overflow-hidden">
            @forelse ($items as $a)
                <div class="p-space-md border-b border-slate-100 flex items-start justify-between gap-3" data-announcement-row>
                    <div class="min-w-0 space-y-1">
                        <p class="font-title-md text-title-md truncate">{{ $a->title }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $a->message }}</p>
                        <div class="flex flex-wrap gap-1">
                            <span class="badge-muted">{{ $aud[$a->audience] ?? $a->audience }}</span>
                            <span class="{{ $a->is_active ? 'badge-paid' : 'badge-muted' }}">{{ $a->is_active ? __('Aktif') : __('Kache') }}</span>
                            @if ($a->ends_at)<span class="badge-muted">{{ __('Jiska') }} {{ $a->ends_at->format('d/m/Y') }}</span>@endif
                        </div>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <form method="POST" action="{{ route('admin.announcements.toggle', $a) }}">@csrf
                            <button class="btn-secondary btn-sm">{{ $a->is_active ? __('Kache') : __('Montre') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" onsubmit="return confirm('{{ __('Efase anons sa a?') }}')">@csrf @method('DELETE')
                            <button class="btn-danger btn-sm">{{ __('Efase') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-space-md"><x-empty-state compact icon="campaign" :title="__('Pa gen anons ankò')" :text="__('Anons ou pibliye yo parèt isit la.')" /></div>
            @endforelse
        </div>
    </div>
</x-admin-layout>
