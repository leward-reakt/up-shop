<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement([
            'percentage',
            'fixed',
        ]);

        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'type' => $type,
            'value' => $type === 'percentage'
                ? fake()->randomElement([5, 10, 15, 20])
                : fake()->numberBetween(5_000, 30_000),
            'minimum_purchase' => fake()->optional()->numberBetween(
                50_000,
                300_000,
            ),
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
