<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayoutDisputed extends Notification
{
    use Queueable;

    public function __construct(public Payout $payout) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $member = $this->payout->member;
        $cycle = $this->payout->cycle;
        $tontine = $cycle?->tontine;

        return [
            'type' => 'payout_disputed',
            'payout_id' => $this->payout->id,
            'tontine_id' => $tontine?->id,
            'tontine_name' => $tontine?->name ?? 'Sòl',
            'member_id' => $member?->id,
            'member_name' => $member?->name ?? 'Manm',
            'amount' => $this->payout->amount,
            'note' => $this->payout->member_note,
            'title' => 'Alèt: Peman konteste pa manm lan!',
            'title_fr' => 'Alerte: Paiement contesté par le membre !',
            'message' => ($member?->name ?? 'Manm lan').' fè konnen li pa resevwa kòb tou li a pou '.($tontine?->name ?? 'sòl la').'.',
            'message_fr' => ($member?->name ?? 'Le membre').' indique n\'avoir pas reçu l\'argent de son tour pour '.($tontine?->name ?? 'la tontine').'.',
            'action_url' => $tontine ? route('tontines.payouts.index', $tontine) : route('dashboard'),
            'icon' => 'warning',
        ];
    }
}
