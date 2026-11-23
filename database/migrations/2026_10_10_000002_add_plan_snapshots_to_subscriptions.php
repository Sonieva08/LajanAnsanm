<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['subscriptions', 'subscription_requests', 'subscription_payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->json('plan_snapshot')->nullable();
            });

            $this->backfillSnapshots($tableName);
        }
    }

    public function down(): void
    {
        foreach (['subscription_payments', 'subscription_requests', 'subscriptions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('plan_snapshot');
            });
        }
    }

    private function backfillSnapshots(string $tableName): void
    {
        DB::table($tableName.' as record')
            ->join('plans as plan', 'record.plan_id', '=', 'plan.id')
            ->select([
                'record.id as record_id',
                'plan.slug as plan_slug',
                'plan.name as plan_name',
                'plan.description as plan_description',
                'plan.badge as plan_badge',
                'plan.is_highlighted as plan_is_highlighted',
                'plan.is_free as plan_is_free',
                'plan.price_monthly as plan_price_monthly',
                'plan.price_quarterly as plan_price_quarterly',
                'plan.price_yearly as plan_price_yearly',
                'plan.max_active_tontines as plan_max_active_tontines',
                'plan.max_members_per_tontine as plan_max_members_per_tontine',
                'plan.features as plan_features',
            ])
            ->orderBy('record.id')
            ->chunk(100, function ($records) use ($tableName) {
                foreach ($records as $record) {
                    $features = is_string($record->plan_features)
                        ? json_decode($record->plan_features, true)
                        : $record->plan_features;

                    $snapshot = [
                        'slug' => $record->plan_slug,
                        'name' => $record->plan_name,
                        'description' => $record->plan_description,
                        'badge' => $record->plan_badge,
                        'is_highlighted' => (bool) $record->plan_is_highlighted,
                        'is_free' => (bool) $record->plan_is_free,
                        'price_monthly' => $record->plan_price_monthly,
                        'price_quarterly' => $record->plan_price_quarterly,
                        'price_yearly' => $record->plan_price_yearly,
                        'max_active_tontines' => $record->plan_max_active_tontines,
                        'max_members_per_tontine' => $record->plan_max_members_per_tontine,
                        'features' => is_array($features) ? $features : [],
                    ];

                    DB::table($tableName)->where('id', $record->record_id)->update([
                        'plan_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                    ]);
                }
            });
    }
};
