<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shipping_address' => json_encode([
                'name' => $this->faker->name(),
                'address' => $this->faker->address(),
                'city' => $this->faker->city(),
                'phone' => $this->faker->phoneNumber(),
            ]),
            'ordered_at' => now(),
            'subTotal' => 100.00,
            'discount_total' => 0,
            'total' => 100.00,
            'cargo_fee' => 0,
            'invoice' => null,
            'status' => 'pending',
            'pre_info_at' => now(),
            'dist_sales_at' => now(),
        ];
    }
}
