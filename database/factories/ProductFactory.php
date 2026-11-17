<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 100, 50000);

        return [
            'category_id' => Category::factory(),
            'name' => fake()->unique()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'description' => fake()->optional()->sentence(),
            'unit' => fake()->randomElement(['piece', 'kg', 'litre', 'carton', 'paquet']),
            'purchase_price' => $purchasePrice,
            'sale_price' => round($purchasePrice * fake()->randomFloat(2, 1.15, 1.8), 2),
            'minimum_stock' => fake()->numberBetween(0, 25),
            'status' => 'active',
        ];
    }
}
