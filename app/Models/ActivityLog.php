<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'tontine_id', 'meta', 'ip'];

    // action => [libellé, couleur]. Les classes CSS sont dans la vue (Tailwind ne scanne pas ce fichier).
    public const ACTIONS = [
        'register' => ['Enskripsyon', 'blue'],
        'login' => ['Koneksyon', 'slate'],
        'tontine_created' => ['Tontin kreye', 'blue'],
        'tontine_deleted' => ['Tontin efase', 'red'],
        'calendar_generated' => ['Kalandriye jenere', 'indigo'],
        'calendar_cancelled' => ['Kalandriye anile', 'red'],
        'contribution_recorded' => ['Peman anrejistre', 'green'],
        'contribution_cancelled' => ['Peman anile', 'red'],
        'payout_made' => ['Vèsman', 'green'],
        'tontine_completed' => ['Tontin fini', 'green'],
        'round_restarted' => ['Sik relanse', 'indigo'],
        'account_disabled' => ['Kont dezaktive', 'red'],
        'account_enabled' => ['Kont reaktive', 'green'],
        'subscription_requested' => ['Demann abònman', 'blue'],
        'subscription_activated' => ['Abònman aktive', 'green'],
        'subscription_rejected' => ['Demann rejte', 'red'],
        'subscription_granted' => ['Abònman bay', 'indigo'],
        'subscription_cancelled' => ['Abònman anile', 'red'],
        'plan_updated' => ['Plan modifye', 'indigo'],
        'maintenance_on' => ['Mòd antretyen aktive', 'red'],
        'maintenance_off' => ['Mòd antretyen fini', 'green'],
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?User $user, array $meta = [], ?int $tontineId = null): self
    {
        return static::create([
            'user_id' => $user?->id,
            'action' => $action,
            'tontine_id' => $tontineId,
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }

    public function label(): string
    {
        return __(self::ACTIONS[$this->action][0] ?? $this->action);
    }

    public function tone(): string
    {
        return self::ACTIONS[$this->action][1] ?? 'slate';
    }

    public function description(): string
    {
        $m = $this->meta ?? [];
        $cur = config('lajanansanm.currency');
        $n = fn ($v) => number_format((float) $v, 0, '.', ',') . ' ' . $cur;

        return match ($this->action) {
            'contribution_recorded', 'contribution_cancelled' => $n($m['amount'] ?? 0),
            'payout_made' => __('Net') . ' ' . $n($m['net'] ?? 0)
                . (($m['fee'] ?? 0) > 0 ? ' · ' . __('kòmisyon') . ' ' . $n($m['fee']) : ''),
            'tontine_created' => $n($m['amount'] ?? 0)
                . (($m['fee_percent'] ?? 0) > 0 ? ' · ' . $m['fee_percent'] . '%' : ''),
            'calendar_generated' => ($m['cycles'] ?? '?') . ' ' . __('tou') . ' · ' . __('Kòl') . ' ' . ($m['round'] ?? 1),
            'round_restarted' => __('Kòl') . ' ' . ($m['round'] ?? '?'),
            'subscription_requested', 'subscription_activated', 'subscription_rejected', 'subscription_granted', 'subscription_cancelled', 'plan_updated'
                => trim(($m['plan'] ?? '') . (isset($m['amount']) ? ' · ' . $n($m['amount']) : '')),
            default => '',
        };
    }
}
