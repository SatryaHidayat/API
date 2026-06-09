<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_can_be_changed_to_paid(): void
    {
        $order = Order::create([
            'user_id' => 3,
            'status' => 'pending',
            'total_price' => 125000,
        ]);

        $this->putJson('/api/orders/' . $order->id, [
            'status' => 'paid',
        ])->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
    }
}
