<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Supplier> */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company' => fake()->company(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'address' => fake()->optional()->streetAddress(),
            'city' => fake()->optional()->city(),
            'country' => fake()->country(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
