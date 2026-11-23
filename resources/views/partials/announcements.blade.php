@auth
    @php $__anons = \App\Models\Announcement::visibleTo(auth()->user()); @endphp
    @foreach ($__anons as $a)
        @php
            $tone = match ($a->type) {
                'danger' => 'bg-error-container text-on-error-container',
                'warning' => 'bg-amber-50 text-amber-900',
                default => 'bg-primary-fixed text-primary-container',
            };
        @endphp
        <div class="mb-space-md rounded-xl px-4 py-3 {{ $tone }}" data-announcement="{{ $a->id }}">
            <p class="font-label-md text-label-md font-bold">{{ $a->title }}</p>
            <p class="font-body-sm text-body-sm">{{ $a->message }}</p>
        </div>
    @endforeach
@endauth
