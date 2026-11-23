<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Tontine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentQrController extends Controller
{
    public function subscription(string $method): StreamedResponse
    {
        abort_unless(in_array($method, ['moncash', 'natcash'], true), 404);
        $payment = Setting::paymentInfo();

        return $this->image($payment[$method.'_qr_path'] ?? null);
    }

    public function tontine(Tontine $tontine, string $method): StreamedResponse
    {
        $this->authorize('view', $tontine);
        abort_unless(in_array($method, ['moncash', 'natcash'], true), 404);

        $payment = $tontine->portalPayment();

        return $this->image($payment[$method.'_qr_path'] ?? null);
    }

    public function memberPortal(Request $request, string $method): StreamedResponse
    {
        abort_unless(in_array($method, ['moncash', 'natcash'], true), 404);

        $member = $request->attributes->get('portal_member');
        $payment = $member->tontine->portalPayment();

        return $this->image($payment[$method.'_qr_path'] ?? null);
    }

    private function image(?string $path): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
