<?php

namespace Database\Factories;

use App\Models\Tontine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tontine>
 */
class TontineFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory(),
            'name' => fake()->word() . ' Tontine',
            'amount_per_period' => 500,
            'organizer_fee_percent' => 0,
            'period_type' => 'weekly',
            'start_date' => now(),
            'status' => 'active',
            'current_round' => 1,
            'member_portal_enabled' => false,
            'portal_payment' => null,
        ];
    }
}
