<?php

namespace App\Http\Controllers;

use App\Http\Requests\TontineRequest;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TontineController extends Controller
{
    public function index(): View
    {
        $tontines = Tontine::where('admin_id', Auth::id())
            ->withCount('members')
            ->latest()
            ->get();

        return view('tontines.index', compact('tontines'));
    }

    public function create(): View
    {
        $this->authorize('create', Tontine::class);

        return view('tontines.create');
    }

    public function store(TontineRequest $request): RedirectResponse
    {
        $this->authorize('create', Tontine::class);

        $tontine = Auth::user()->tontines()->create($request->validated());

        return redirect()
            ->route('tontines.show', $tontine)
            ->with('status', __('Tontine kreye.'));
    }

    public function show(Tontine $tontine): View
    {
        $this->authorize('view', $tontine);

        $tontine->load('members');

        $currentCycle = $tontine->cycles()
            ->where('status', 'open')
            ->with('beneficiary')
            ->withCount('contributions')
            ->withSum('contributions', 'amount')
            ->first();

        return view('tontines.show', compact('tontine', 'currentCycle'));
    }

    public function edit(Tontine $tontine): View
    {
        $this->authorize('update', $tontine);

        return view('tontines.edit', compact('tontine'));
    }

    public function update(TontineRequest $request, Tontine $tontine): RedirectResponse
    {
        $this->authorize('update', $tontine);

        // Si le calendrier existe, TontineRequest ne laisse passer que le nom
        $tontine->update($request->validated());

        return redirect()
            ->route('tontines.show', $tontine)
            ->with('status', __('Tontine modifye.'));
    }

    public function destroy(Tontine $tontine): RedirectResponse
    {
        $this->authorize('delete', $tontine);

        $tontine->delete();

        return redirect()
            ->route('tontines.index')
            ->with('status', __('Tontine efase.'));
    }
}
