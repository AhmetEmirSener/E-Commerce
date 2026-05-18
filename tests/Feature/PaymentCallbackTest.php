<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use App\Models\Payment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Contracts\IyzicoServiceInterface;
use App\Services\StockService;
use Tests\Fakes\FakeComplete3DSResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $status = 'pending', string $paymentStatus = 'pending'): Order
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => $paymentStatus,
            'amount' => 100,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        return $order;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function successful_payment_updates_order_and_payment_status(): void
    {
        $order = $this->makeOrder();

        $this->mock(IyzicoServiceInterface::class, function ($mock) {
            $mock->shouldReceive('complete3DS')
                ->once()
                ->andReturn(new FakeComplete3DSResult(status: 'success'));
        });

        $response = $this->post('/api/payment/callback', [
            'status' => 'success',
            'mdStatus' => 1,
            'paymentId' => '123456789',
            'conversationId' => $order->id,
        ]);

        $response->assertRedirectContains('payment/result?token=');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'paid',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function callback_fails_when_order_not_found(): void
    {
        $response = $this->post('/api/payment/callback', [
            'status' => 'success',
            'mdStatus' => 1,
            'paymentId' => '123456789',
            'conversationId' => 99999,
        ]);

        $response->assertRedirectContains('status=failed');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function callback_redirects_when_already_paid(): void
    {
        $order = $this->makeOrder(status: 'paid', paymentStatus: 'paid');

        $response = $this->post('/api/payment/callback', [
            'status' => 'success',
            'mdStatus' => 1,
            'paymentId' => '123456789',
            'conversationId' => $order->id,
        ]);

        $response->assertRedirectContains('status=success_already_paid');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function callback_fails_when_3ds_fails(): void
    {
        $order = $this->makeOrder();

        $response = $this->post('/api/payment/callback', [
            'status' => 'failure',
            'mdStatus' => 0,
            'paymentId' => '123456789',
            'conversationId' => $order->id,
            'mdErrorMsg' => '3DS doğrulama başarısız',
        ]);

        $response->assertRedirectContains('checkout?payment_error');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'failed', // değişmemeli
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function callback_fails_when_iyzico_complete_fails(): void
    {
        $order = $this->makeOrder();

        $this->mock(IyzicoServiceInterface::class, function ($mock) {
            $mock->shouldReceive('complete3DS')
                ->once()
                ->andReturn(new FakeComplete3DSResult(
                    status: 'failure',
                    errorMessage: 'Kart reddedildi',
                    errorCode: '10051'
                ));
        });

        $response = $this->post('/api/payment/callback', [
            'status' => 'success',
            'mdStatus' => 1,
            'paymentId' => '123456789',
            'conversationId' => $order->id,
        ]);

        $response->assertRedirectContains('checkout?payment_error');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'failed',
        ]);
    }
}