<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        /** @var string $name */
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => fake()->unique()->slug(3),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(10_000, 500_000),
            'stock_quantity' => fake()->numberBetween(0, 50),
            'low_stock_threshold' => 5,
            'is_active' => true,
            'is_featured' => false,
            'meta_title' => Str::title($name),
            'meta_description' => fake()->sentence(),
        ];
    }
}
