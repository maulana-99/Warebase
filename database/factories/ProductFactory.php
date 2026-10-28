<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'location_id' => Location::factory(),
            'sku' => fake()->unique()->bothify('SKU-#####'),
            'name' => fake()->words(3, true),
            'unit' => fake()->randomElement(['pcs', 'rim', 'box', 'botol']),
            'min_stock' => fake()->numberBetween(5, 20),
            'description' => null,
        ];
    }
}
