<?php

namespace App\Console\Commands;

use App\Models\Cycle;
use App\Models\Tontine;
use Illuminate\Console\Command;

class RemindLateMembers extends Command
{
    protected $signature = 'lajan:remind-late {--tontine= : ID d\'une tontine spécifique}';

    protected $description = 'Jwenn epi prepare rapèl WhatsApp pou tout manm ki an reta oswa ki poko peye';

    public function handle(): int
    {
        $query = Cycle::with(['tontine.members', 'contributions'])
            ->where('status', 'open');

        if ($tontineId = $this->option('tontine')) {
            $query->where('tontine_id', $tontineId);
        }

        $cycles = $query->get();

        if ($cycles->isEmpty()) {
            $this->info('Pa gen okenn tou ouvè ki bezwen rapèl kounye a.');
            return self::SUCCESS;
        }

        $totalReminders = 0;

        foreach ($cycles as $cycle) {
            $tontine = $cycle->tontine;
            if (! $tontine) {
                continue;
            }

            $paidMemberIds = $cycle->contributions->pluck('member_id')->all();
            $unpaidMembers = $tontine->members
                ->where('is_active', true)
                ->whereNotIn('id', $paidMemberIds);

            if ($unpaidMembers->isEmpty()) {
                continue;
            }

            $this->line("--------------------------------------------------");
            $this->info("Sòl: {$tontine->name} (Tou #{$cycle->cycle_number}) - Dat limit: {$cycle->deadline->format('d/m/Y')}");

            foreach ($unpaidMembers as $member) {
                $totalReminders++;
                $digits = preg_replace('/\D+/', '', (string) $member->phone);
                if (strlen($digits) === 8) {
                    $digits = '509' . $digits;
                }

                $msg = "Bonjou {$member->name}, rapèl: kotizasyon {$tontine->amount_per_period} HTG pou {$tontine->name} (tou #{$cycle->cycle_number}) dwe peye anvan {$cycle->deadline->format('d/m/Y')}.";
                $waUrl = $digits ? "https://wa.me/{$digits}?text=" . rawurlencode($msg) : "Pa gen nimewo valid";

                $this->line("  * {$member->name} ({$member->phone}): {$waUrl}");
            }
        }

        $this->info("\nTotal rapèl prepare: {$totalReminders}");

        return self::SUCCESS;
    }
}
