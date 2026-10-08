<x-admin-layout active="activity" :title="__('Jounal aktivite')">
    @php $csvUrl = route('admin.activity', array_filter($filters) + ['format' => 'csv']); @endphp

    <div class="flex flex-wrap items-start justify-between gap-4 mb-space-lg">
        <div>
            <h1 class="font-headline-lg text-headline-lg">{{ __('Jounal aktivite') }}</h1>
            <p class="text-on-surface-variant">{{ __('Rejis aksyon kle yo. Pa gen non ni telefòn manm.') }}</p>
        </div>
        <a href="{{ $csvUrl }}" class="btn-primary"><span class="material-symbols-outlined">download</span> {{ __('Eksporte CSV') }}</a>
    </div>

    <div class="flex gap-space-sm overflow-x-auto pb-1 mb-space-md">
        @forelse ($summary->take(6) as $act => $count)
            <a href="{{ route('admin.activity', array_filter(array_merge($filters, ['action' => $act]))) }}"
               class="shrink-0 card !py-2 !px-4 {{ $filters['action'] === $act ? 'ring-2 ring-primary-container' : '' }}">
                <p class="font-label-sm text-label-sm text-on-surface-variant">{{ __(\App\Models\ActivityLog::ACTIONS[$act][0] ?? $act) }}</p>
                <p class="font-title-lg text-title-lg tabular-nums">{{ number_format($count) }}</p>
            </a>
        @empty
            <p class="text-on-surface-variant">{{ __('Pa gen aktivite pou filt sa yo.') }}</p>
        @endforelse
    </div>

    <form method="GET" class="card mb-space-md grid sm:grid-cols-2 lg:grid-cols-5 gap-space-sm items-end">
        <div><label class="label">{{ __('Soti') }}</label><input type="date" name="from" value="{{ $filters['from'] }}" class="input"></div>
        <div><label class="label">{{ __('Jiska') }}</label><input type="date" name="to" value="{{ $filters['to'] }}" class="input"></div>
        <div><label class="label">{{ __('Aksyon') }}</label>
            <select name="action" class="input">
                <option value="">{{ __('Tout') }}</option>
                @foreach (\App\Models\ActivityLog::ACTIONS as $key => [$label, $_])
                    <option value="{{ $key }}" @selected($filters['action'] === $key)>{{ __($label) }}</option>
                @endforeach
            </select></div>
        <div><label class="label">{{ __('Itilizatè') }}</label><input name="q" value="{{ $filters['q'] }}" class="input" placeholder="{{ __('Non oswa imèl') }}"></div>
        <button class="btn-primary">{{ __('Filtre') }}</button>
    </form>

    <div class="card !p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-on-surface-variant">
                    <tr><th class="p-3">{{ __('Dat') }}</th><th class="p-3">{{ __('Itilizatè') }}</th><th class="p-3">{{ __('Aksyon') }}</th><th class="p-3">{{ __('Detay') }}</th><th class="p-3 hidden lg:table-cell">IP</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-surface-container-low">
                            <td class="p-3 whitespace-nowrap font-body-sm text-body-sm text-on-surface-variant">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="p-3"><span class="block font-label-lg text-label-lg">{{ $log->user?->name ?? __('Sistèm') }}</span><span class="block font-body-sm text-body-sm text-on-surface-variant">{{ $log->user?->email }}</span></td>
                            <td class="p-3">@include('admin._chip', ['log' => $log])</td>
                            <td class="p-3 font-body-sm text-body-sm tabular-nums">{{ $log->description() ?: '—' }}</td>
                            <td class="p-3 hidden lg:table-cell font-body-sm text-body-sm text-on-surface-variant">{{ $log->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-space-md"><x-empty-state compact icon="calendar_view_week" :title="__('Pa gen done anrejistre pou peryòd sa a')" :text="__('Chanje filtè yo oswa dat la pou w wè jounal aktivite a.')"
                        :href="route('admin.activity')" :action="__('Reyajiste filtè yo')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100">{{ $logs->links() }}</div>
    </div>
</x-admin-layout>
