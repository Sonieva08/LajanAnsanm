@if (config('services.google.client_id'))
    <div class="relative my-space-md text-center font-body-sm text-body-sm text-on-surface-variant">
        <div class="absolute inset-x-0 top-1/2 border-t border-slate-200"></div>
        <span class="relative bg-surface-container-lowest px-3">{{ __('oswa') }}</span>
    </div>
    <a href="{{ route('google.redirect') }}"
       class="w-full min-h-[48px] rounded-xl border border-slate-300 bg-white flex items-center justify-center gap-3 font-label-lg text-label-lg text-on-surface hover:bg-surface-container transition">
        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.5l6.7-6.7C35.6 2.4 30.2 0 24 0 14.6 0 6.5 5.4 2.6 13.3l7.8 6.1C12.3 13.5 17.7 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.1 5.3-4.5 6.9l7.3 5.7c4.2-3.9 6.6-9.7 6.6-17.1z"/>
            <path fill="#FBBC05" d="M10.4 28.6c-.5-1.4-.8-2.9-.8-4.6s.3-3.2.8-4.6l-7.8-6.1C.9 16.5 0 20.1 0 24s.9 7.5 2.6 10.7l7.8-6.1z"/>
            <path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.3-5.7c-2 1.4-4.7 2.2-8.6 2.2-6.3 0-11.7-4.2-13.6-9.9l-7.8 6.1C6.5 42.6 14.6 48 24 48z"/>
        </svg>
        {{ __('Konekte ak Google') }}
    </a>
@endif
