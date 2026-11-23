<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    // Page publique : les avis PUBLIÉS + le formulaire pour les utilisateurs connectés
    public function index(Request $request): View
    {
        return view('reviews.index', [
            'reviews' => Review::approved()->with('user:id,name')->latest('id')->paginate(9),
            'summary' => Review::publicSummary(),
            'mine' => $request->user()?->review,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        // Requête directe (pas la relation) : évite de garder en cache un « null » sur l'objet utilisateur
        $review = Review::where('user_id', $request->user()->id)->first() ?? new Review();
        $review->fill($data);
        $review->user_id = $request->user()->id;
        // Toute création ou modification repasse par la modération avant d'être visible
        $review->status = 'pending';
        $review->save();

        return redirect()->route('reviews.index')->with('status', __('Mèsi! Avi ou an ap tann apwobasyon avan l parèt piblikman.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->review?->delete();

        return redirect()->route('reviews.index')->with('status', __('Avi ou an efase.'));
    }
}
