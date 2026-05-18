<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => 'credit_card',
            'payment_provider' => 'iyzico',
            'provider_payment_id' => null,
            'amount' => 100.00,
            'installment_count' => 1,
            'installment_fee' => 0,
            'status' => 'pending',
            'saved_card_id' => null,
            'last_four' => null,
            'paid_at' => null,
            'save_card' => false,
            'card_bank' => null,
        ];
    }
}
