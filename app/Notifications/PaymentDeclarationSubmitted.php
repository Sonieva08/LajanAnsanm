<?php

namespace App\Notifications;

use App\Models\PaymentDeclaration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentDeclarationSubmitted extends Notification
{
    use Queueable;

    public function __construct(public PaymentDeclaration $declaration) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $member = $this->declaration->member;
        $cycle = $this->declaration->cycle;
        $tontine = $cycle?->tontine;

        return [
            'type' => 'payment_declaration',
            'declaration_id' => $this->declaration->id,
            'tontine_id' => $tontine?->id,
            'tontine_name' => $tontine?->name ?? 'Sòl',
            'member_id' => $member?->id,
            'member_name' => $member?->name ?? 'Manm',
            'amount' => $this->declaration->amount,
            'payment_method' => $this->declaration->payment_method,
            'transaction_ref' => $this->declaration->transaction_ref,
            'title' => 'Nouvo deklarasyon peman',
            'title_fr' => 'Nouvelle déclaration de paiement',
            'message' => ($member?->name ?? 'Yon manm').' deklare yon peman pou '.($tontine?->name ?? 'sòl la').' (Tou #'.($cycle?->cycle_number ?? '').').',
            'message_fr' => ($member?->name ?? 'Un membre').' a déclaré un paiement pour '.($tontine?->name ?? 'la tontine').' (Tour #'.($cycle?->cycle_number ?? '').').',
            'action_url' => $tontine ? route('tontines.declarations.index', $tontine) : route('dashboard'),
            'icon' => 'payments',
        ];
    }
}
