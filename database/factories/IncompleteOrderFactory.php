<?php

namespace Database\Factories;

use App\Models\IncompleteOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncompleteOrder>
 */
class IncompleteOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_token' => fake()->uuid(),
            'customer_phone' => '01'.fake()->numberBetween(3, 9).fake()->numerify('########'),
            'customer_name' => fake()->name(),
            'customer_address' => fake()->address(),
            'delivery_area' => 'inside',
            'items' => [
                ['product_id' => 1, 'name' => fake()->words(3, true), 'price' => 15500.0, 'qty' => 1],
            ],
            'cart_total' => 15500,
            'status' => 'new',
            'last_captured_at' => now(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'last_captured_at' => now()->subDays(IncompleteOrder::RETENTION_DAYS + 1),
        ]);
    }
}
