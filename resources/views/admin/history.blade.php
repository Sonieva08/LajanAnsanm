<x-admin-layout active="activity" :title="__('Istorik chanjman')">
    <div class="mb-space-lg">
        <h1 class="font-headline-lg text-headline-lg text-primary">{{ __('Istorik chanjman') }}</h1>
        <p class="text-on-surface-variant">{{ __('Ki moun ki chanje plan yo, règ yo, anons yo, sekirite a ak rapèl yo, ak ki lè.') }}</p>
    </div>

    <div class="card !p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-on-surface-variant">
                    <tr><th class="p-3">{{ __('Dat') }}</th><th class="p-3">{{ __('Ki moun') }}</th><th class="p-3">{{ __('Chanjman') }}</th><th class="p-3">{{ __('Detay') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="p-3 whitespace-nowrap font-body-sm text-body-sm text-on-surface-variant tabular-nums">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="p-3 font-label-lg text-label-lg">{{ $log->user?->name ?? __('Sistèm') }}</td>
                            <td class="p-3">@include('admin._chip', ['log' => $log])</td>
                            <td class="p-3 font-body-sm text-body-sm">{{ $log->description() ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-space-md"><x-empty-state compact icon="history" :title="__('Pa gen chanjman ankò')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100">{{ $logs->links() }}</div>
    </div>
</x-admin-layout>
