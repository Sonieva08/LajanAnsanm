<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayoutReceivedConfirmed extends Notification
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
            'type' => 'payout_received',
            'payout_id' => $this->payout->id,
            'tontine_id' => $tontine?->id,
            'tontine_name' => $tontine?->name ?? 'Sòl',
            'member_id' => $member?->id,
            'member_name' => $member?->name ?? 'Manm',
            'amount' => $this->payout->amount,
            'title' => 'Peman resevwa e konfime',
            'title_fr' => 'Paiement reçu et confirmé',
            'message' => ($member?->name ?? 'Manm lan').' konfime li byen resevwa lajan tou li a pou '.($tontine?->name ?? 'sòl la').'.',
            'message_fr' => ($member?->name ?? 'Le membre').' a confirmé avoir bien reçu le paiement de son tour pour '.($tontine?->name ?? 'la tontine').'.',
            'action_url' => $tontine ? route('tontines.payouts.index', $tontine) : route('dashboard'),
            'icon' => 'check_circle',
        ];
    }
}
