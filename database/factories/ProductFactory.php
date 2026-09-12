<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->unique()->words(3, true),
            'price' => fake()->numberBetween(100000, 5000000),
            'quantity' => fake()->numberBetween(10, 200),
            'description' => fake()->sentence(),
        ];
    }
}
