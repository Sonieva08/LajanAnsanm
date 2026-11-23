<x-admin-layout active="reviews" :title="__('Avi itilizatè yo')">
    @php
        $labels = ['pending' => __('Ap tann'), 'approved' => __('Pibliye'), 'hidden' => __('Kache')];
        $tones = ['pending' => 'badge-wait', 'approved' => 'badge-paid', 'hidden' => 'badge-muted'];
    @endphp

    <div class="mb-space-lg">
        <h1 class="font-headline-lg text-headline-lg text-primary">{{ __('Avi itilizatè yo') }}</h1>
        <p class="text-on-surface-variant">{{ __('Yon avi parèt sou paj piblik la sèlman apre ou apwouve l.') }}</p>
    </div>

    <div class="flex flex-wrap gap-2 mb-space-md">
        @foreach ($labels as $key => $label)
            <a href="{{ route('admin.reviews', ['status' => $key]) }}"
               class="{{ $status === $key ? 'bg-primary-container text-on-primary' : 'bg-surface-container-lowest text-on-surface-variant border border-slate-200' }} rounded-full px-4 py-2 font-label-md text-label-md">
                {{ $label }} <span class="opacity-70">({{ $counts[$key] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    <div class="card !p-0 overflow-hidden">
        @forelse ($reviews as $review)
            <div class="p-space-md border-b border-slate-100 flex flex-col sm:flex-row sm:items-start justify-between gap-3" data-review-row>
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-star-rating :value="$review->rating" size="text-[18px]" />
                        <span class="{{ $tones[$review->status] ?? 'badge-muted' }}">{{ $labels[$review->status] ?? $review->status }}</span>
                    </div>
                    <p class="font-body-md text-body-md text-on-surface break-words">{{ $review->comment }}</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ $review->user?->name }} · {{ $review->user?->email }} · {{ $review->updated_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                <div class="flex gap-1 shrink-0">
                    @if ($review->status !== 'approved')
                        <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">@csrf
                            <button class="btn-primary btn-sm">{{ __('Apwouve') }}</button>
                        </form>
                    @endif
                    @if ($review->status !== 'hidden')
                        <form method="POST" action="{{ route('admin.reviews.hide', $review) }}">@csrf
                            <button class="btn-secondary btn-sm">{{ __('Kache') }}</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" x-data @submit="if (! confirm(@js(__('Efase avi sa a?')))) $event.preventDefault()">@csrf @method('DELETE')
                        <button class="btn-danger btn-sm">{{ __('Efase') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="p-space-lg text-center text-on-surface-variant">{{ __('Pa gen avi nan lis sa a.') }}</p>
        @endforelse
    </div>

    <div class="mt-space-md">{{ $reviews->links() }}</div>
</x-admin-layout>
