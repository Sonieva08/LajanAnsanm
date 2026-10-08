<x-admin-layout active="users" :title="__('Itilizatè')">
    @php
        $cur = config('lajanansanm.currency');
        $initials = fn ($n) => collect(preg_split('/\s+/', trim((string) $n)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
        $qs = fn (array $extra = []) => array_filter(array_merge(['q' => $q, 'status' => $filter === 'all' ? null : $filter], $extra), fn ($v) => $v !== null && $v !== '');
    @endphp

    <div class="mb-space-lg">
        <h1 class="font-headline-lg text-headline-lg">{{ __('Jesyon itilizatè yo') }}</h1>
        <p class="text-on-surface-variant">{{ __('Kont òganizatè yo, aktivite yo ak volim yo. Pa gen done manm prive isit la.') }}</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-space-md mb-space-lg">
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Total kont') }}</p><p class="font-headline-md text-headline-md tabular-nums">{{ number_format($stats['total']) }}</p></div>
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Aktif (30 jou)') }}</p><p class="font-headline-md text-headline-md tabular-nums text-emerald-600">{{ number_format($stats['active']) }}</p></div>
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Nouvo (7 jou)') }}</p><p class="font-headline-md text-headline-md tabular-nums">{{ number_format($stats['new7']) }}</p></div>
        <div class="card"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Dezaktive') }}</p><p class="font-headline-md text-headline-md tabular-nums {{ $stats['disabled'] ? 'text-secondary' : '' }}">{{ number_format($stats['disabled']) }}</p></div>
    </div>

    <form method="GET" class="card mb-space-md space-y-space-sm">
        <div class="flex flex-wrap gap-2">
            <input name="q" value="{{ $q }}" class="input flex-1 min-w-[200px]" placeholder="{{ __('Chèche non oswa imèl...') }}">
            @if ($filter !== 'all')<input type="hidden" name="status" value="{{ $filter }}">@endif
            <button class="btn-primary">{{ __('Chèche') }}</button>
        </div>
        <div class="flex flex-wrap gap-2 font-label-md text-label-md">
            @foreach (['all' => __('Tout'), 'active' => __('Aktif'), 'inactive' => __('Inaktif'), 'disabled' => __('Dezaktive')] as $key => $label)
                <a href="{{ route('admin.users', array_filter(['q' => $q, 'status' => $key === 'all' ? null : $key])) }}"
                   class="px-4 py-1.5 rounded-full {{ $filter === $key ? 'bg-primary-container text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }}">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    <div class="grid gap-space-md {{ $selected ? 'xl:grid-cols-[1fr_380px]' : '' }}">
        <div class="card !p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="p-3">{{ __('Òganizatè') }}</th><th class="p-3 text-right">{{ __('Tontin') }}</th><th class="p-3 text-right">{{ __('Manm') }}</th><th class="p-3 text-right">{{ __('Volim total') }}</th><th class="p-3">{{ __('Dènye vizit') }}</th><th class="p-3">{{ __('Estati') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $u)
                            <tr class="hover:bg-surface-container-low {{ $selected && $selected['user']->id === $u->id ? 'bg-primary-fixed/40' : '' }}">
                                <td class="p-3">
                                    <a href="{{ route('admin.users', $qs(['user' => $u->id, 'page' => $users->currentPage()])) }}" class="flex items-center gap-3">
                                        <span class="w-10 h-10 shrink-0 rounded-full bg-primary-fixed text-primary-container flex items-center justify-center font-label-md text-label-md">{{ $initials($u->name) }}</span>
                                        <span class="min-w-0"><span class="block font-label-lg text-label-lg truncate">{{ $u->name }}</span><span class="block font-body-sm text-body-sm text-on-surface-variant truncate">{{ $u->email }}</span></span>
                                    </a>
                                </td>
                                <td class="p-3 text-right tabular-nums">{{ $u->tontines_count }}</td>
                                <td class="p-3 text-right tabular-nums">{{ $u->members_total }}</td>
                                <td class="p-3 text-right tabular-nums">{{ number_format((float) $u->volume_total, 0, '.', ',') }} <span class="text-on-surface-variant text-xs">{{ $cur }}</span></td>
                                <td class="p-3 font-body-sm text-body-sm text-on-surface-variant whitespace-nowrap">{{ $u->last_seen_at ? $u->last_seen_at->locale(app()->getLocale())->diffForHumans() : __('Jamè') }}</td>
                                <td class="p-3">
                                    @if ($u->disabled_at)<span class="badge-late">{{ __('Dezaktive') }}</span>
                                    @elseif ($u->last_seen_at && $u->last_seen_at->gte(now()->subDays(30)))<span class="badge-paid">{{ __('Aktif') }}</span>
                                    @else<span class="badge-muted">{{ __('Inaktif') }}</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-space-md"><x-empty-state art="search" :title="__('Pa gen rezilta pou rechèch sa a')" :text="__('Nou pa jwenn okenn itilizatè ki koresponn. Eseye yon lòt rechèch oswa verifye òtograf la.')"
                        :href="route('admin.users')" :action="__('Netwaye rechèch la')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-t border-slate-100">{{ $users->links() }}</div>
        </div>

        @if ($selected)
            @php $su = $selected['user']; @endphp
            <aside class="card space-y-space-md h-fit">
                <div class="flex items-center justify-between">
                    <h2 class="font-title-lg text-title-lg">{{ __('Detay kont') }}</h2>
                    <a href="{{ route('admin.users', $qs(['page' => $users->currentPage()])) }}" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center" aria-label="{{ __('Fèmen') }}"><span class="material-symbols-outlined text-[18px]">close</span></a>
                </div>
                <div class="bg-primary-fixed/50 rounded-xl p-space-md flex items-center gap-3">
                    <span class="w-12 h-12 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-lg text-label-lg">{{ $initials($su->name) }}</span>
                    <div class="min-w-0"><p class="font-title-md text-title-md truncate">{{ $su->name }}</p><p class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $su->email }}</p></div>
                </div>
                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 font-body-sm text-body-sm">
                    <dt class="text-on-surface-variant">{{ __('Enskri') }}</dt><dd class="text-right">{{ $su->created_at->format('d/m/Y') }}</dd>
                    <dt class="text-on-surface-variant">{{ __('Dènye vizit') }}</dt><dd class="text-right">{{ $su->last_seen_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt class="text-on-surface-variant">{{ __('Imèl verifye') }}</dt><dd class="text-right">{{ $su->email_verified_at ? __('Wi') : __('Non') }}</dd>
                </dl>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-surface-container-low rounded-xl p-2"><p class="font-title-lg text-title-lg tabular-nums">{{ $selected['tontines_total'] }}</p><p class="font-label-sm text-label-sm text-on-surface-variant">{{ __('Tontin') }}</p></div>
                    <div class="bg-surface-container-low rounded-xl p-2"><p class="font-title-lg text-title-lg tabular-nums">{{ $selected['tontines_active'] }}</p><p class="font-label-sm text-label-sm text-on-surface-variant">{{ __('Aktif') }}</p></div>
                    <div class="bg-surface-container-low rounded-xl p-2"><p class="font-title-lg text-title-lg tabular-nums">{{ $selected['members'] }}</p><p class="font-label-sm text-label-sm text-on-surface-variant">{{ __('Manm') }}</p></div>
                </div>
                <div class="bg-primary-fixed/50 rounded-xl p-space-md">
                    <p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Volim total jere') }}</p>
                    <p class="font-headline-md text-headline-md tabular-nums">{{ number_format($selected['volume'], 0, '.', ',') }} <span class="font-title-md text-title-md">{{ $cur }}</span></p>
                </div>
                <div>
                    <h3 class="font-label-lg text-label-lg mb-2">{{ __('Dènye aktivite') }}</h3>
                    <div class="space-y-2">
                        @forelse ($selected['logs'] as $log)
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">@include('admin._chip', ['log' => $log])<p class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $log->description() }}</p></div>
                                <span class="font-body-sm text-body-sm text-on-surface-variant whitespace-nowrap">{{ $log->created_at?->format('d/m H:i') }}</span>
                            </div>
                        @empty
                            <p class="text-on-surface-variant font-body-sm text-body-sm">{{ __('Pa gen aktivite ankò.') }}</p>
                        @endforelse
                    </div>
                </div>
                @php $ent = $selected['ent']; @endphp
                <div class="rounded-xl border border-slate-200 p-space-md space-y-space-sm" data-user-plan>
                    <div class="flex items-center justify-between">
                        <h3 class="font-label-lg text-label-lg">{{ __('Plan ak abònman') }}</h3>
                        <span class="badge-info">{{ $ent->plan->name }}</span>
                    </div>
                    @if ($ent->subscription)
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ $ent->state === 'expired' ? __('Ekspire') : __('Ekspire le') }} {{ $ent->subscription->ends_at->format('d/m/Y') }} ({{ $ent->state }})
                        </p>
                    @else
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Plan gratis la.') }}</p>
                    @endif
                    <form method="POST" action="{{ route('admin.users.grant', $su) }}" class="space-y-2">
                        @csrf
                        <select name="plan_id" class="input" required>
                            @foreach ($selected['plans'] as $pl)<option value="{{ $pl->id }}">{{ $pl->name }}</option>@endforeach
                        </select>
                        <input type="date" name="ends_at" class="input" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                        <input name="note" class="input" maxlength="300" placeholder="{{ __('Nòt (opsyonèl)') }}">
                        <button class="btn-primary btn-sm w-full">{{ __('Bay oswa pwolonje abònman') }}</button>
                    </form>
                    @if ($ent->subscription && $ent->state !== 'expired')
                        <form method="POST" action="{{ route('admin.users.revoke', $su) }}" onsubmit="return confirm('{{ __('Retounen itilizatè a sou plan gratis la?') }}')">
                            @csrf @method('DELETE')
                            <button class="font-label-md text-label-md text-secondary underline">{{ __('Retounen sou plan gratis') }}</button>
                        </form>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.users.toggle', $su) }}"
                      onsubmit="return confirm('{{ $su->disabled_at ? __('Reaktive kont sa a?') : __('Dezaktive kont sa a? Li p ap ka konekte ankò.') }}')">
                    @csrf
                    <button class="w-full {{ $su->disabled_at ? 'btn-success' : 'btn-secondary !text-secondary' }}">
                        <span class="material-symbols-outlined">{{ $su->disabled_at ? 'check_circle' : 'block' }}</span>
                        {{ $su->disabled_at ? __('Reaktive kont sa a') : __('Dezaktive kont sa a') }}
                    </button>
                </form>
            </aside>
        @endif
    </div>
</x-admin-layout>
