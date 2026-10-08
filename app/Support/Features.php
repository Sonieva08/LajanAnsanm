<?php

namespace App\Support;

/**
 * Fonctions qui peuvent être incluses dans un plan. Chacune correspond à du code qui existe réellement.
 * Une nouvelle fonction doit être ajoutée ici quand elle est construite : elle apparaît alors dans la console.
 */
final class Features
{
    public const ALL = [
        'csv_export' => ['Ekspòte done an CSV / Excel', 'Telechaje rapò yo nan Excel.', 'table_view'],
        'restart_round' => ['Relanse yon nouvo sik', 'Kòmanse yon nouvo sik lè tou yo fini.', 'autorenew'],
        'whatsapp_reminders' => ['Rapèl WhatsApp', 'Voye rapèl bay manm ki an reta.', 'chat'],
        'organizer_fee' => ['Kòmisyon òganizatè', 'Retni yon pousantaj sou chak vèsman.', 'percent'],
    ];

    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    public static function label(string $key): string
    {
        return __(self::ALL[$key][0] ?? $key);
    }
}
