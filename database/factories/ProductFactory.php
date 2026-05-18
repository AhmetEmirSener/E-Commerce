<?php

namespace Database\Factories;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
           'name' => $this->faker->words(3, true),
            'category_id' => Category::factory(),
            'brand_id' => null,
            'price' => 100.00,
            'stock' => 10,
            'campaign_id' => null,
            'is_campaign_on' => false,
            'discount_price' => null,
            'discount_stock' => null,
            'is_discount_active' => false,
            'image' => null,
            'weight' => null,
            'slug' => $this->faker->unique()->slug(),
            'status' => 'aktif',
            'features' => null,
        ];
    }
}
