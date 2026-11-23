<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), Review::STATUSES, true) ? $request->query('status') : 'pending';

        return view('admin.reviews.index', [
            'status' => $status,
            'counts' => Review::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'reviews' => Review::with('user:id,name,email')->where('status', $status)->latest('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function approve(Request $request, Review $review): RedirectResponse
    {
        return $this->setStatus($request, $review, 'approved', __('Avi a pibliye.'));
    }

    public function hide(Request $request, Review $review): RedirectResponse
    {
        return $this->setStatus($request, $review, 'hidden', __('Avi a kache.'));
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        ActivityLog::record('review_moderated', $request->user(), ['review_id' => $review->id, 'deleted' => true]);
        $review->delete();

        return back()->with('status', __('Avi a efase.'));
    }

    private function setStatus(Request $request, Review $review, string $status, string $message): RedirectResponse
    {
        $review->status = $status;
        $review->save();
        ActivityLog::record('review_moderated', $request->user(), ['review_id' => $review->id, 'status' => $status]);

        return back()->with('status', $message);
    }
}
