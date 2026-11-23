<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['audience', 'type', 'faq_id', 'query'];

    // Retire tout ce qui ressemble à un numéro de téléphone, un e-mail ou un code avant d'enregistrer une question libre
    public static function clean(?string $query): ?string
    {
        $q = trim((string) $query);
        $q = preg_replace('/[^\s@]+@[^\s@]+\.[^\s@]+/u', '#', $q);
        $q = preg_replace('/[\d][\d\s\-+().]{3,}[\d]/u', '#', $q);
        $q = preg_replace('/\s+/u', ' ', $q);
        $q = mb_substr($q, 0, 120);

        return $q !== '' ? $q : null;
    }
}
