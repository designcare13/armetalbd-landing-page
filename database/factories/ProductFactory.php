<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'price' => 10000,
            'old_price' => null,
            'discount_text' => null,
            'short_description' => fake()->sentence(),
            'specs' => [],
            'rating' => 5.0,
            'reviews_count' => 25,
            'stock' => 100,
            'is_featured' => false,
            'is_active' => true,
        ];
    }
}
